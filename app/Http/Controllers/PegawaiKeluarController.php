<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Events\NotifApproval;
use App\Models\PegawaiKeluar;
use App\Models\MasterLookup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PegawaiKeluarController extends Controller
{
    private const FILE_DISK = 'public';
    private const FILE_DIRECTORY = 'pegawai_keluar_file_path';

    public function index()
    {
        date_default_timezone_set('Asia/Jakarta');
        $title = 'Pegawai Keluar';
        $nama = request()->input('nama');
        $mulai = request()->input('mulai');
        $akhir = request()->input('akhir');

        $pegawai_keluars = PegawaiKeluar::with(['user.Jabatan.man', 'approvedBy'])
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
