<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Events\NotifApproval;
use App\Models\DokumenPengembalianAset;
use App\Models\InventoryStockTransaction;
use App\Models\Lokasi;
use App\Models\PegawaiKeluar;
use App\Models\MasterLookup;
use App\Notifications\UserNotification;
use App\Services\LayananAsetPegawaiKeluar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PegawaiKeluarController extends Controller
{
    private const FILE_DISK = 'public';
    private const FILE_DIRECTORY = 'pegawai_keluar_file_path';
    private const ROUTE_EXIT = '/exit';
    private const ROUTE_MY_RETURN_BAST = '/my-inventory-return-bast';
    private const ROLE_ADMIN = 'admin';
    private const ROLE_USER = 'user';

    private $layananAset;

    public function __construct(LayananAsetPegawaiKeluar $layananAset)
    {
        $this->layananAset = $layananAset;
    }

    public function index(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $title = 'Pegawai Keluar';
        $nama = $request->input('nama');
        $mulai = $request->input('mulai');
        $akhir = $request->input('akhir');
        $user = auth()->user();

        $pegawai_keluars = PegawaiKeluar::with(['user.Jabatan.man', 'approvedBy', 'assetClearances'])
                            ->when($mulai && $akhir, function ($query) use ($mulai, $akhir) {
                                $query->whereBetween('tanggal', [$mulai, $akhir]);
                            })
                            ->when($nama, function ($query) use ($nama) {
                                $query->whereHas('user', function ($q) use ($nama) {
                                    $q->where('name', 'LIKE', '%' . $nama . '%');
                                });
                            })
                            ->when($this->isRegularEmployee($user), function ($query) use ($user) {
                                $query->where('user_id', $user->id);
                            })
                            ->when($this->isDepartmentManager($user), function ($query) use ($user) {
                                $query->whereHas('user', function ($q) use ($user) {
                                    $q->where('jabatan_id', $user->jabatan_id);
                                });
                            })
                            ->orderBy('tanggal', 'DESC')
                            ->paginate(10)
                            ->withQueryString();

        return view($this->adminOrUserView('pegawai-keluar.index', 'pegawai-keluar.indexUser'), compact(
            'title',
            'pegawai_keluars'
        ));
    }

    public function tambah()
    {
        $title = 'Pegawai Keluar';
        $users = User::orderBy('name')->get();
        $exitTypes = MasterLookup::getByType(MasterLookup::TYPE_EXIT);

        return view($this->adminOrUserView('pegawai-keluar.tambah', 'pegawai-keluar.tambahUser'), compact(
            'title',
            'users',
            'exitTypes',
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedRequestData($request);
        $validated['status'] = PegawaiKeluar::STATUS_PENDING;

        $pegawai_keluar = PegawaiKeluar::create($validated);
        $this->notifyApprover($pegawai_keluar);

        return redirect(self::ROUTE_EXIT)->with('success', 'Data Berhasil Disimpan');
    }

    public function edit($id)
    {
        $title = 'Pegawai Keluar';
        $users = User::orderBy('name')->get();
        $pegawai_keluar = PegawaiKeluar::with('user.Jabatan.man')->findOrFail($id);
        abort_unless($this->canModify($pegawai_keluar), 403);

        $exitTypes = MasterLookup::getByType(MasterLookup::TYPE_EXIT);

        return view($this->adminOrUserView('pegawai-keluar.edit', 'pegawai-keluar.editUser'), compact(
            'title',
            'users',
            'pegawai_keluar',
            'exitTypes',
        ));
    }

    public function update(Request $request, $id)
    {
        $pegawai_keluar = PegawaiKeluar::with('user.Jabatan.man')->findOrFail($id);
        abort_unless($this->canModify($pegawai_keluar), 403);

        $validated = $this->validatedRequestData($request, $pegawai_keluar);
        $pegawai_keluar->update($validated);
        $this->notifyApprover($pegawai_keluar->fresh(['user.Jabatan.man']));

        return redirect(self::ROUTE_EXIT)->with('success', 'Data Berhasil Diupdate');
    }

    public function approval(Request $request, $id)
    {
        $pegawai_keluar = PegawaiKeluar::with('user.Jabatan.man')->findOrFail($id);
        abort_unless($this->canApprove($pegawai_keluar), 403);

        $validated = $this->validatedApprovalData($request);

        if ($validated['status'] === PegawaiKeluar::STATUS_APPROVED) {
            $blockedApproval = $this->rejectApprovalWhenAssetPending($pegawai_keluar);

            if ($blockedApproval) {
                return $blockedApproval;
            }
        }

        $this->applyApproval($pegawai_keluar, $validated);

        return redirect(self::ROUTE_EXIT)->with('success', 'Data Berhasil Diupdate');
    }

    public function delete($id)
    {
        $pegawai_keluar = PegawaiKeluar::with('user.Jabatan.man')->findOrFail($id);
        abort_unless($this->canModify($pegawai_keluar), 403);

        if ($pegawai_keluar->pegawai_keluar_file_path) {
            Storage::disk(self::FILE_DISK)->delete($pegawai_keluar->pegawai_keluar_file_path);
        }

        $pegawai_keluar->delete();
        return redirect(self::ROUTE_EXIT)->with('success', 'Data Berhasil Didelete');
    }

    public function assets($id)
    {
        abort_unless($this->isAdmin(), 403);

        $title = 'Clearance Aset Pegawai Keluar';
        $pegawai_keluar = PegawaiKeluar::with(['user.Jabatan', 'approvedBy'])->findOrFail($id);
        $clearances = $this->layananAset->syncClearances($pegawai_keluar);
        $lokasi = Lokasi::orderBy('nama_lokasi')->get();
        $users = User::with('Jabatan')->orderBy('name')->get();

        return view('pegawai-keluar.aset', compact('title', 'pegawai_keluar', 'clearances', 'lokasi', 'users'));
    }

    public function returnAsset(Request $request, $exit, $transaction)
    {
        abort_unless($this->isAdmin(), 403);

        $pegawai_keluar = PegawaiKeluar::with('user.Jabatan')->findOrFail($exit);
        $stockTransaction = InventoryStockTransaction::with(['inventory', 'penerima.Jabatan'])->findOrFail($transaction);
        $validated = $this->validatedReturnAssetData($request);

        $result = $this->layananAset->processReturn($pegawai_keluar, $stockTransaction, $validated, auth()->user());
        $this->notifyReturnSigners($result['document'], auth()->user());

        return redirect($this->assetClearanceRoute($pegawai_keluar))
            ->with('success', 'Pengembalian aset berhasil diproses dan BAST Pengembalian dibuat: ' . $result['document']->nomor_surat);
    }

    public function waiveAsset(Request $request, $exit, $transaction)
    {
        abort_unless($this->isAdmin(), 403);

        $pegawai_keluar = PegawaiKeluar::with('user')->findOrFail($exit);
        $stockTransaction = InventoryStockTransaction::with('inventory')->findOrFail($transaction);
        $validated = $this->validatedWaiveAssetData($request);

        $this->layananAset->waive($pegawai_keluar, $stockTransaction, $validated, auth()->user());

        return redirect($this->assetClearanceRoute($pegawai_keluar))
            ->with('success', 'Aset berhasil diberi pengecualian clearance.');
    }

    public function downloadReturnDocument($document)
    {
        abort_unless($this->isAdmin(), 403);

        $document = DokumenPengembalianAset::with(['inventory', 'pegawaiKeluar.user'])->findOrFail($document);

        return $this->downloadReturnBast($document);
    }

    public function myReturnBastDocuments()
    {
        $title = 'BAST Pengembalian Aset Saya';
        $documents = $this->myReturnDocumentQuery(auth()->id())
            ->latest('inventory_return_documents.created_at')
            ->paginate(10)
            ->withQueryString();

        return view('inventory.bast_pengembalian_saya', compact('title', 'documents'));
    }

    public function showMyReturnBastDocument($id)
    {
        $title = 'Detail BAST Pengembalian Aset';
        $document = $this->myReturnDocumentQuery(auth()->id())->findOrFail($id);

        return view('inventory.detail_bast_pengembalian_saya', compact('title', 'document'));
    }

    public function signMyReturnBastDocument(Request $request, $id, $role)
    {
        $role = (string) $role;
        $roleConfig = $this->returnBastSignatureRoleConfig($role);

        $document = $this->myReturnDocumentQuery(auth()->id())->findOrFail($id);

        if (!$document->canUserSignRole(auth()->user(), $role)) {
            abort(404);
        }

        $request->validate($this->signatureValidationRules(), $this->signatureValidationMessages());

        if (!$document->{$roleConfig['signed_at']}) {
            $this->layananAset->storeSignature(
                $document,
                $role,
                $request->input('signature_data'),
                auth()->user(),
                $request->ip(),
                $request->userAgent()
            );
        }

        return redirect(self::ROUTE_MY_RETURN_BAST . '/' . $document->id)
            ->with('success', $roleConfig['label'] . ' berhasil ditandatangani dan PDF sudah diperbarui.');
    }

    public function downloadMyReturnBastDocument($id)
    {
        $document = $this->myReturnDocumentQuery(auth()->id())->findOrFail($id);

        return $this->downloadReturnBast($document);
    }

    private function adminOrUserView($adminView, $userView)
    {
        return $this->isAdmin() ? $adminView : $userView;
    }

    private function assetClearanceRoute(PegawaiKeluar $pegawai_keluar)
    {
        return self::ROUTE_EXIT . '/' . $pegawai_keluar->id . '/assets';
    }

    private function validatedApprovalData(Request $request): array
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(PegawaiKeluar::approvalStatuses())],
            'notes' => 'nullable|string',
        ]);

        $validated['approved_by'] = auth()->id();
        $validated['tanggal_approval'] = now()->toDateString();

        return $validated;
    }

    private function applyApproval(PegawaiKeluar $pegawai_keluar, array $validated): void
    {
        DB::transaction(function () use ($pegawai_keluar, $validated) {
            $pegawai_keluar->update($validated);

            if ($validated['status'] === PegawaiKeluar::STATUS_APPROVED && $pegawai_keluar->user) {
                $pegawai_keluar->user->update([
                    'masa_berlaku' => $pegawai_keluar->tanggal
                ]);
            }
        });
    }

    private function returnBastSignatureRoleConfig($role)
    {
        $roleConfig = DokumenPengembalianAset::signatureRoles()[$role] ?? null;

        if (!$roleConfig) {
            abort(404);
        }

        return $roleConfig;
    }

    private function signatureValidationRules(): array
    {
        return [
            'agreement' => 'accepted',
            'signature_data' => ['required', 'string', 'regex:/^data:image\/png;base64,/'],
        ];
    }

    private function signatureValidationMessages(): array
    {
        return [
            'agreement.accepted' => 'Centang persetujuan sebelum tanda tangan.',
            'signature_data.required' => 'Bubuhkan tanda tangan di kotak tanda tangan.',
            'signature_data.regex' => 'Format tanda tangan tidak valid.',
        ];
    }

    private function rejectApprovalWhenAssetPending(PegawaiKeluar $pegawai_keluar)
    {
        $pendingClearances = $this->layananAset->pendingClearances($pegawai_keluar);

        if ($pendingClearances->isEmpty()) {
            return null;
        }

        return redirect($this->assetClearanceRedirect($pegawai_keluar))
            ->with('error', 'Approval belum bisa diproses. Aset berikut belum dikembalikan atau dikecualikan: ' . $this->pendingAssetNames($pendingClearances));
    }

    private function pendingAssetNames($pendingClearances)
    {
        return $pendingClearances->map(function ($clearance) {
            $inventory = optional($clearance->originalTransaction)->inventory;
            $kodeBarang = $inventory->kode_barang ?? null;
            $namaBarang = $inventory->nama_barang ?? 'Aset kantor';

            return trim(($kodeBarang ? $kodeBarang . ' - ' : '') . $namaBarang);
        })->implode(', ');
    }

    private function assetClearanceRedirect(PegawaiKeluar $pegawai_keluar)
    {
        if (!$this->isAdmin()) {
            return self::ROUTE_EXIT;
        }

        return $this->assetClearanceRoute($pegawai_keluar);
    }

    private function validatedReturnAssetData(Request $request)
    {
        return $request->validate([
            'tanggal_kembali' => 'required|date',
            'kondisi_barang' => 'required|string|max:255',
            'kelengkapan' => 'required|string|max:255',
            'status_barang' => 'nullable|string|max:255',
            'lokasi_id' => 'nullable|exists:lokasis,id',
            'it_receiver_user_id' => 'nullable|exists:users,id',
            'known_by_user_id' => 'nullable|exists:users,id',
            'catatan' => 'nullable|string',
        ]);
    }

    private function validatedWaiveAssetData(Request $request)
    {
        return $request->validate([
            'waiver_reason' => 'required|string|min:5',
        ], [
            'waiver_reason.required' => 'Alasan pengecualian wajib diisi.',
            'waiver_reason.min' => 'Alasan pengecualian minimal 5 karakter.',
        ]);
    }

    private function downloadReturnBast(DokumenPengembalianAset $document)
    {
        $document = $this->layananAset->storePdf($document);

        if (!$document->file_pdf || !Storage::disk(self::FILE_DISK)->exists($document->file_pdf)) {
            abort(404);
        }

        return Storage::disk(self::FILE_DISK)->download(
            $document->file_pdf,
            'bast-pengembalian-' . $this->safeFilename($document->nomor_surat) . '.pdf'
        );
    }

    private function validatedRequestData(Request $request, ?PegawaiKeluar $pegawaiKeluar = null)
    {
        $isAdmin = $this->isAdmin();
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
        $action = self::ROUTE_EXIT . '?nama=' . urlencode($pegawaiKeluar->user->name) . '&mulai=' . $pegawaiKeluar->tanggal . '&akhir=' . $pegawaiKeluar->tanggal;
        $url = url($action);

        $approver->messages = [
            'user_id' => auth()->id(),
            'from' => auth()->user()->name,
            'message' => $notif,
            'action' => $action,
        ];
        $approver->notify(new UserNotification);

        NotifApproval::dispatch($type, $approver->id, $notif, $url);
    }

    private function notifyReturnSigners(DokumenPengembalianAset $document, User $sender)
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
                'action' => self::ROUTE_MY_RETURN_BAST . '/' . $document->id,
                'inventory_return_document_id' => $document->id,
            ];
            $signer->notify(new UserNotification);
        }
    }

    private function myReturnDocumentQuery($userId)
    {
        return DokumenPengembalianAset::with([
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

    private function isAdmin()
    {
        return auth()->user() && auth()->user()->is_admin == self::ROLE_ADMIN;
    }

    private function isRegularEmployee(?User $user)
    {
        return $user
            && $user->is_admin == self::ROLE_USER
            && $user->Jabatan
            && $user->Jabatan->manager != $user->id;
    }

    private function isDepartmentManager(?User $user)
    {
        return $user
            && $user->is_admin == self::ROLE_USER
            && $user->Jabatan
            && $user->Jabatan->manager == $user->id;
    }

    private function canModify(PegawaiKeluar $pegawaiKeluar)
    {
        $user = auth()->user();

        return $this->isAdmin()
            || $pegawaiKeluar->user_id == $user->id
            || $this->canApprove($pegawaiKeluar);
    }

    private function canApprove(PegawaiKeluar $pegawaiKeluar)
    {
        $user = auth()->user();

        return $this->isAdmin()
            || optional(optional($pegawaiKeluar->user)->Jabatan)->manager == $user->id;
    }
}
