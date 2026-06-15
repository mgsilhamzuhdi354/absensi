<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Events\NotifApproval;
use App\Models\InventoryReturnDocument;
use App\Models\InventoryStockTransaction;
use App\Models\Lokasi;
use App\Models\PegawaiKeluar;
use App\Models\MasterLookup;
use App\Notifications\UserNotification;
use App\Services\PegawaiKeluarAssetService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PegawaiKeluarController extends Controller
{
    private const FILE_DISK = 'public';
    private const FILE_DIRECTORY = 'pegawai_keluar_file_path';

    private $assetService;

    public function __construct(PegawaiKeluarAssetService $assetService)
    {
        $this->assetService = $assetService;
    }

    public function index()
    {
        date_default_timezone_set('Asia/Jakarta');
        $title = 'Pegawai Keluar';
        $nama = request()->input('nama');
        $mulai = request()->input('mulai');
        $akhir = request()->input('akhir');

        $pegawai_keluars = PegawaiKeluar::with(['user.Jabatan.man', 'approvedBy', 'assetClearances'])
                            ->when($mulai && $akhir, function ($query) use ($mulai, $akhir) {
                                $query->whereBetween('tanggal', [$mulai, $akhir]);
                            })
                            ->when($nama, function ($query) use ($nama) {
                                $query->whereHas('user', function ($q) use ($nama) {
                                    $q->where('name', 'LIKE', '%' . $nama . '%');
                                });
                            })
                            ->when(auth()->user() && auth()->user()->Jabatan && auth()->user()->Jabatan->manager != auth()->user()->id && auth()->user()->is_admin == 'user', function ($query) {
                                $query->where('user_id', auth()->user()->id);
                            })
                            ->when(auth()->user() && auth()->user()->Jabatan && auth()->user()->Jabatan->manager == auth()->user()->id && auth()->user()->is_admin == 'user', function ($query) {
                                $query->whereHas('user', function ($q) {
                                    $q->where('jabatan_id', auth()->user()->jabatan_id);
                                });
                            })
                            ->orderBy('tanggal', 'DESC')
                            ->paginate(10)
                            ->withQueryString();



        if (auth()->user()->is_admin == 'admin') {
            return view('pegawai-keluar.index', compact(
                'title',
                'pegawai_keluars'
            ));
        } else {
            return view('pegawai-keluar.indexUser', compact(
                'title',
                'pegawai_keluars'
            ));
        }

    }

    public function tambah()
    {
        $title = 'Pegawai Keluar';
        $users = User::orderBy('name')->get();
        $exitTypes = MasterLookup::getByType(MasterLookup::TYPE_EXIT);

        if (auth()->user()->is_admin == 'admin') {
            return view('pegawai-keluar.tambah', compact(
                'title',
                'users',
                'exitTypes',
            ));
        } else {
            return view('pegawai-keluar.tambahUser', compact(
                'title',
                'users',
                'exitTypes',
            ));
        }

    }

    public function store(Request $request)
    {
        $validated = $this->validatedRequestData($request);
        $validated['status'] = 'PENDING';

        $pegawai_keluar = PegawaiKeluar::create($validated);
        $this->notifyApprover($pegawai_keluar);

        return redirect('/exit')->with('success', 'Data Berhasil Disimpan');
    }

    public function edit($id)
    {
        $title = 'Pegawai Keluar';
        $users = User::orderBy('name')->get();
        $pegawai_keluar = PegawaiKeluar::with('user.Jabatan.man')->findOrFail($id);
        abort_unless($this->canModify($pegawai_keluar), 403);

        $exitTypes = MasterLookup::getByType(MasterLookup::TYPE_EXIT);

        if (auth()->user()->is_admin == 'admin') {
            return view('pegawai-keluar.edit', compact(
                'title',
                'users',
                'pegawai_keluar',
                'exitTypes',
            ));
        } else {
            return view('pegawai-keluar.editUser', compact(
                'title',
                'users',
                'pegawai_keluar',
                'exitTypes',
            ));
        }

    }

    public function update(Request $request, $id)
    {
        $pegawai_keluar = PegawaiKeluar::with('user.Jabatan.man')->findOrFail($id);
        abort_unless($this->canModify($pegawai_keluar), 403);

        $validated = $this->validatedRequestData($request, $pegawai_keluar);
        $pegawai_keluar->update($validated);
        $this->notifyApprover($pegawai_keluar->fresh(['user.Jabatan.man']));

        return redirect('/exit')->with('success', 'Data Berhasil Diupdate');
    }

    public function approval(Request $request, $id)
    {
        $pegawai_keluar = PegawaiKeluar::with('user.Jabatan.man')->findOrFail($id);
        abort_unless($this->canApprove($pegawai_keluar), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['APPROVED', 'REJECTED'])],
            'notes' => 'nullable|string',
        ]);

        $validated['approved_by'] = auth()->id();
        $validated['tanggal_approval'] = now()->toDateString();

        if ($validated['status'] == 'APPROVED') {
            $pendingClearances = $this->assetService->pendingClearances($pegawai_keluar);

            if ($pendingClearances->isNotEmpty()) {
                $assetNames = $pendingClearances->map(function ($clearance) {
                    $inventory = optional($clearance->originalTransaction)->inventory;

                    return trim(($inventory->kode_barang ? $inventory->kode_barang . ' - ' : '') . ($inventory->nama_barang ?? 'Aset kantor'));
                })->implode(', ');

                $target = auth()->user()->is_admin == 'admin'
                    ? '/exit/' . $pegawai_keluar->id . '/assets'
                    : '/exit';

                return redirect($target)
                    ->with('error', 'Approval belum bisa diproses. Aset berikut belum dikembalikan atau dikecualikan: ' . $assetNames);
            }
        }

        DB::transaction(function () use ($pegawai_keluar, $validated) {
            $pegawai_keluar->update($validated);

            if ($validated['status'] == 'APPROVED' && $pegawai_keluar->user) {
                $pegawai_keluar->user->update([
                    'masa_berlaku' => $pegawai_keluar->tanggal
                ]);
            }
        });

        return redirect('/exit')->with('success', 'Data Berhasil Diupdate');
    }

    public function delete($id)
    {
        $pegawai_keluar = PegawaiKeluar::with('user.Jabatan.man')->findOrFail($id);
        abort_unless($this->canModify($pegawai_keluar), 403);

        if ($pegawai_keluar->pegawai_keluar_file_path) {
            Storage::disk(self::FILE_DISK)->delete($pegawai_keluar->pegawai_keluar_file_path);
        }

        $pegawai_keluar->delete();
        return redirect('/exit')->with('success', 'Data Berhasil Didelete');
    }

    public function assets($id)
    {
        abort_unless(auth()->user()->is_admin == 'admin', 403);

        $title = 'Clearance Aset Pegawai Keluar';
        $pegawai_keluar = PegawaiKeluar::with(['user.Jabatan', 'approvedBy'])->findOrFail($id);
        $clearances = $this->assetService->syncClearances($pegawai_keluar);
        $lokasi = Lokasi::orderBy('nama_lokasi')->get();
        $users = User::with('Jabatan')->orderBy('name')->get();

        return view('pegawai-keluar.assets', compact('title', 'pegawai_keluar', 'clearances', 'lokasi', 'users'));
    }

    public function returnAsset(Request $request, $exit, $transaction)
    {
        abort_unless(auth()->user()->is_admin == 'admin', 403);

        $pegawai_keluar = PegawaiKeluar::with('user.Jabatan')->findOrFail($exit);
        $stockTransaction = InventoryStockTransaction::with(['inventory', 'penerima.Jabatan'])->findOrFail($transaction);
        $validated = $request->validate([
            'tanggal_kembali' => 'required|date',
            'kondisi_barang' => 'required|string|max:255',
            'kelengkapan' => 'required|string|max:255',
            'status_barang' => 'nullable|string|max:255',
            'lokasi_id' => 'nullable|exists:lokasis,id',
            'it_receiver_user_id' => 'nullable|exists:users,id',
            'known_by_user_id' => 'nullable|exists:users,id',
            'catatan' => 'nullable|string',
        ]);

        $result = $this->assetService->processReturn($pegawai_keluar, $stockTransaction, $validated, auth()->user());
        $this->notifyReturnSigners($result['document'], auth()->user());

        return redirect('/exit/' . $pegawai_keluar->id . '/assets')
            ->with('success', 'Pengembalian aset berhasil diproses dan BAST Pengembalian dibuat: ' . $result['document']->nomor_surat);
    }

    public function waiveAsset(Request $request, $exit, $transaction)
    {
        abort_unless(auth()->user()->is_admin == 'admin', 403);

        $pegawai_keluar = PegawaiKeluar::with('user')->findOrFail($exit);
        $stockTransaction = InventoryStockTransaction::with('inventory')->findOrFail($transaction);
        $validated = $request->validate([
            'waiver_reason' => 'required|string|min:5',
        ], [
            'waiver_reason.required' => 'Alasan pengecualian wajib diisi.',
            'waiver_reason.min' => 'Alasan pengecualian minimal 5 karakter.',
        ]);

        $this->assetService->waive($pegawai_keluar, $stockTransaction, $validated, auth()->user());

        return redirect('/exit/' . $pegawai_keluar->id . '/assets')
            ->with('success', 'Aset berhasil diberi pengecualian clearance.');
    }

    public function downloadReturnDocument($document)
    {
        abort_unless(auth()->user()->is_admin == 'admin', 403);

        $document = InventoryReturnDocument::with(['inventory', 'pegawaiKeluar.user'])->findOrFail($document);
        $document = $this->assetService->storePdf($document);

        if (!$document->file_pdf || !Storage::disk('public')->exists($document->file_pdf)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $document->file_pdf,
            'bast-pengembalian-' . $this->safeFilename($document->nomor_surat) . '.pdf'
        );
    }

    public function myReturnBastDocuments()
    {
        $title = 'BAST Pengembalian Aset Saya';
        $documents = $this->myReturnDocumentQuery(auth()->id())
            ->latest('inventory_return_documents.created_at')
            ->paginate(10)
            ->withQueryString();

        return view('inventory.my_return_bast_index', compact('title', 'documents'));
    }

    public function showMyReturnBastDocument($id)
    {
        $title = 'Detail BAST Pengembalian Aset';
        $document = $this->myReturnDocumentQuery(auth()->id())->findOrFail($id);

        return view('inventory.my_return_bast_show', compact('title', 'document'));
    }

    public function signMyReturnBastDocument(Request $request, $id, $role)
    {
        $role = (string) $role;
        $roleConfig = InventoryReturnDocument::signatureRoles()[$role] ?? null;

        if (!$roleConfig) {
            abort(404);
        }

        $document = $this->myReturnDocumentQuery(auth()->id())->findOrFail($id);

        if (!$document->canUserSignRole(auth()->user(), $role)) {
            abort(404);
        }

        $request->validate([
            'agreement' => 'accepted',
            'signature_data' => ['required', 'string', 'regex:/^data:image\/png;base64,/'],
        ], [
            'agreement.accepted' => 'Centang persetujuan sebelum tanda tangan.',
            'signature_data.required' => 'Bubuhkan tanda tangan di kotak tanda tangan.',
            'signature_data.regex' => 'Format tanda tangan tidak valid.',
        ]);

        if (!$document->{$roleConfig['signed_at']}) {
            $this->assetService->storeSignature(
                $document,
                $role,
                $request->input('signature_data'),
                auth()->user(),
                $request->ip(),
                $request->userAgent()
            );
        }

        return redirect('/my-inventory-return-bast/' . $document->id)
            ->with('success', $roleConfig['label'] . ' berhasil ditandatangani dan PDF sudah diperbarui.');
    }

    public function downloadMyReturnBastDocument($id)
    {
        $document = $this->myReturnDocumentQuery(auth()->id())->findOrFail($id);
        $document = $this->assetService->storePdf($document);

        if (!$document->file_pdf || !Storage::disk('public')->exists($document->file_pdf)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $document->file_pdf,
            'bast-pengembalian-' . $this->safeFilename($document->nomor_surat) . '.pdf'
        );
    }

    private function validatedRequestData(Request $request, ?PegawaiKeluar $pegawaiKeluar = null)
    {
        $isAdmin = auth()->user()->is_admin == 'admin';
        $validated = $request->validate([
            'user_id' => $isAdmin ? ['required', 'integer', 'exists:users,id'] : ['nullable'],
            'jenis' => ['required', 'string', Rule::in(MasterLookup::valuesForType(MasterLookup::TYPE_EXIT))],
            'alasan' => ['required', 'string'],
            'tanggal' => ['required', 'date'],
            'pegawai_keluar_file_path' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        unset($validated['pegawai_keluar_file_path']);

        if (!$isAdmin) {
            $validated['user_id'] = $pegawaiKeluar ? $pegawaiKeluar->user_id : auth()->id();
        }

        if ($request->hasFile('pegawai_keluar_file_path')) {
            if ($pegawaiKeluar && $pegawaiKeluar->pegawai_keluar_file_path) {
                Storage::disk(self::FILE_DISK)->delete($pegawaiKeluar->pegawai_keluar_file_path);
            }

            $file = $request->file('pegawai_keluar_file_path');
            $validated['pegawai_keluar_file_path'] = $file->store(self::FILE_DIRECTORY, self::FILE_DISK);
            $validated['pegawai_keluar_file_name'] = $file->getClientOriginalName();
        }

        return $validated;
    }

    private function notifyApprover(PegawaiKeluar $pegawaiKeluar)
    {
        $pegawaiKeluar->loadMissing('user.Jabatan.man');

        $approver = optional(optional($pegawaiKeluar->user)->Jabatan)->man;
        if (!$approver) {
            return;
        }

        $type = 'Approval';
        $notif = 'Pengajuan Pegawai Keluar Dari ' . auth()->user()->name . ' Butuh Approval Anda';
        $action = '/exit?nama=' . urlencode($pegawaiKeluar->user->name) . '&mulai=' . $pegawaiKeluar->tanggal . '&akhir=' . $pegawaiKeluar->tanggal;
        $url = url($action);

        $approver->messages = [
            'user_id' => auth()->id(),
            'from' => auth()->user()->name,
            'message' => $notif,
            'action' => $action,
        ];
        $approver->notify(new \App\Notifications\UserNotification);

        NotifApproval::dispatch($type, $approver->id, $notif, $url);
    }

    private function notifyReturnSigners(InventoryReturnDocument $document, User $sender)
    {
        $document->loadMissing('inventory', 'employee', 'itReceiver', 'knownBy');
        $inventoryName = $document->inventory->nama_barang ?? 'aset kantor';
        $message = 'BAST Pengembalian ' . $document->nomor_surat . ' untuk ' . $inventoryName . ' menunggu tanda tangan Anda.';
        $signers = collect([
            $document->employee,
            $document->itReceiver,
            $document->knownBy,
        ])->filter()->unique('id');

        foreach ($signers as $signer) {
            $signer->messages = [
                'user_id' => $sender->id,
                'from' => $sender->name,
                'message' => $message,
                'action' => '/my-inventory-return-bast/' . $document->id,
                'inventory_return_document_id' => $document->id,
            ];
            $signer->notify(new UserNotification);
        }
    }

    private function myReturnDocumentQuery($userId)
    {
        return InventoryReturnDocument::with([
                'inventory.lokasi',
                'inventory.jabatan',
                'employee.Jabatan',
                'itReceiver.Jabatan',
                'knownBy.Jabatan',
                'pegawaiKeluar.user.Jabatan',
                'originalTransaction',
                'returnTransaction',
            ])
            ->where(function ($query) use ($userId) {
                $query->where('employee_user_id', $userId)
                    ->orWhere('it_receiver_user_id', $userId)
                    ->orWhere('known_by_user_id', $userId);
            });
    }

    private function safeFilename($value)
    {
        return trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $value), '-') ?: 'inventory-return';
    }

    private function canModify(PegawaiKeluar $pegawaiKeluar)
    {
        $user = auth()->user();

        return $user->is_admin == 'admin'
            || $pegawaiKeluar->user_id == $user->id
            || $this->canApprove($pegawaiKeluar);
    }

    private function canApprove(PegawaiKeluar $pegawaiKeluar)
    {
        $user = auth()->user();

        return $user->is_admin == 'admin'
            || optional(optional($pegawaiKeluar->user)->Jabatan)->manager == $user->id;
    }
}
