<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use App\Models\dinasLuar;
use App\Models\PengajuanDinasLuar;
use Illuminate\Http\Request;
use App\Events\NotifApproval;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class PengajuanDinasLuarController extends Controller
{
    // ========================
    // KARYAWAN: Lihat pengajuan saya
    // ========================
    public function index()
    {
        $mulai = request()->input('mulai');
        $akhir = request()->input('akhir');

        $data = PengajuanDinasLuar::where('user_id', auth()->user()->id)
            ->when($mulai && $akhir, function ($q) use ($mulai, $akhir) {
                return $q->whereBetween('tanggal_mulai', [$mulai, $akhir]);
            })
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        // User biasa pakai template mobile (app), admin pakai dashboard
        $view = auth()->user()->is_admin === 'admin' ? 'pengajuan-dinas-luar.index' : 'pengajuan-dinas-luar.indexuser';

        return view($view, [
            'title' => 'Pengajuan Dinas Luar Saya',
            'data'  => $data,
        ]);
    }

    // ========================
    // KARYAWAN: Form tambah pengajuan
    // ========================
    public function tambah()
    {
        $shifts = Shift::orderBy('nama_shift')->get();

        // User biasa pakai template mobile (app), admin pakai dashboard
        $view = auth()->user()->is_admin === 'admin' ? 'pengajuan-dinas-luar.tambah' : 'pengajuan-dinas-luar.tambahuser';

        return view($view, [
            'title'  => 'Ajukan Dinas Luar',
            'shifts' => $shifts,
        ]);
    }

    // ========================
    // KARYAWAN: Simpan pengajuan
    // ========================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift_id'      => 'required|exists:shifts,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan'        => 'required|string|max:500',
            'lokasi_tujuan' => 'nullable|string|max:255',
            'foto_bukti'    => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'lat_pengajuan'  => 'nullable|string',
            'long_pengajuan' => 'nullable|string',
        ]);

        if ($this->hasOverlappingRequest(auth()->user()->id, $validated['tanggal_mulai'], $validated['tanggal_akhir'])) {
            return redirect('/pengajuan-dinas-luar')->with('error', 'Anda sudah memiliki pengajuan dinas luar Pending/Approved pada rentang tanggal tersebut.');
        }

        // Handle foto bukti upload
        if ($request->hasFile('foto_bukti')) {
            $validated['foto_bukti'] = $request->file('foto_bukti')->store('foto_bukti_dinas', 'public');
        }

        $validated['user_id'] = auth()->user()->id;
        $validated['status']  = 'Pending';

        $pengajuan = PengajuanDinasLuar::create($validated);

        // Kirim notifikasi ke admin
        $this->notifyAdmins($request, $pengajuan);

        return redirect('/pengajuan-dinas-luar')->with('success', 'Pengajuan Dinas Luar berhasil diajukan, menunggu persetujuan admin.');
    }

    // ========================
    // KARYAWAN: Simpan pengajuan dari halaman /dinas-luar (inline form)
    // ========================
    public function storeFromDinasLuar(Request $request)
    {
        $validated = $request->validate([
            'shift_id'      => 'required|exists:shifts,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan'        => 'required|string|max:500',
            'lokasi_tujuan' => 'nullable|string|max:255',
            'foto_bukti'    => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'lat_pengajuan'  => 'nullable|string',
            'long_pengajuan' => 'nullable|string',
        ]);

        if ($this->hasOverlappingRequest(auth()->user()->id, $validated['tanggal_mulai'], $validated['tanggal_akhir'])) {
            return redirect('/dinas-luar')->with('error', 'Anda sudah memiliki pengajuan dinas luar Pending/Approved pada rentang tanggal tersebut.');
        }

        // Handle foto bukti upload
        if ($request->hasFile('foto_bukti')) {
            $validated['foto_bukti'] = $request->file('foto_bukti')->store('foto_bukti_dinas', 'public');
        }

        $validated['user_id'] = auth()->user()->id;
        $validated['status']  = 'Pending';

        $pengajuan = PengajuanDinasLuar::create($validated);

        // Kirim notifikasi ke admin
        $this->notifyAdmins($request, $pengajuan);

        return redirect('/dinas-luar')->with('success', 'Pengajuan Dinas Luar berhasil diajukan! Menunggu persetujuan admin.');
    }

    // ========================
    // Helper: Kirim notifikasi ke semua admin
    // ========================
    private function notifyAdmins(Request $request, PengajuanDinasLuar $pengajuan)
    {
        $admin_users = User::where('is_admin', 'admin')->get();
        foreach ($admin_users as $admin) {
            $type  = 'Approval';
            $notif = 'Pengajuan Dinas Luar dari ' . auth()->user()->name . ' (' . $pengajuan->tanggal_mulai . ' s/d ' . $pengajuan->tanggal_akhir . ') menunggu approval Anda.';
            $url   = url('/data-pengajuan-dinas');

            $admin->messages = [
                'user_id' => auth()->user()->id,
                'from'    => auth()->user()->name,
                'message' => $notif,
                'action'  => '/data-pengajuan-dinas',
            ];
            $admin->notify(new \App\Notifications\UserNotification);

            NotifApproval::dispatch($type, $admin->id, $notif, $url);
            WhatsAppService::send($admin->telepon, $notif . "\n" . $url);
        }
    }

    // ========================
    // KARYAWAN: Hapus pengajuan (hanya jika Pending)
    // ========================
    public function delete($id)
    {
        $pengajuan = PengajuanDinasLuar::where('id', $id)
            ->where('user_id', auth()->user()->id)
            ->firstOrFail();

        if ($pengajuan->status !== 'Pending') {
            Alert::error('Gagal', 'Hanya pengajuan berstatus Pending yang dapat dihapus.');
            return redirect('/pengajuan-dinas-luar');
        }

        $pengajuan->delete();
        return redirect('/pengajuan-dinas-luar')->with('success', 'Pengajuan berhasil dihapus.');
    }

    // ========================
    // ADMIN: Lihat semua pengajuan
    // ========================
    public function adminIndex()
    {
        $mulai   = request()->input('mulai');
        $akhir   = request()->input('akhir');
        $user_id = request()->input('user_id');
        $status  = request()->input('status');

        $data = PengajuanDinasLuar::with(['User', 'Shift', 'approvedBy'])
            ->when($mulai && $akhir, function ($q) use ($mulai, $akhir) {
                return $q->whereBetween('tanggal_mulai', [$mulai, $akhir]);
            })
            ->when($user_id, function ($q) use ($user_id) {
                return $q->where('user_id', $user_id);
            })
            ->when($status, function ($q) use ($status) {
                return $q->where('status', $status);
            })
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $users = User::orderBy('name')->get();

        return view('pengajuan-dinas-luar.admin', [
            'title' => 'Data Pengajuan Dinas Luar',
            'data'  => $data,
            'users' => $users,
        ]);
    }

    // ========================
    // ADMIN: Approve / Tolak pengajuan
    // ========================
    public function approval(Request $request, $id)
    {
        $request->validate([
            'status'  => 'required|in:Approved,Ditolak',
            'catatan' => 'nullable|string|max:500',
        ]);

        $pengajuan = PengajuanDinasLuar::findOrFail($id);
        DB::transaction(function () use ($pengajuan, $request) {
            $pengajuan->update([
                'status'        => $request->status,
                'catatan'       => $request->catatan,
                'user_approval' => auth()->user()->id,
            ]);

            if ($request->status === 'Approved') {
                $this->createDinasLuarRows($pengajuan);
            }
        });

        $karyawan = User::findOrFail($pengajuan->user_id);

        // Jika Approved → buat record dinas_luars untuk tiap hari dalam range
        if ($request->status === 'Approved') {
            $type  = 'Approved';
            $notif = 'Pengajuan Dinas Luar Anda (' . $pengajuan->tanggal_mulai . ' s/d ' . $pengajuan->tanggal_akhir . ') telah disetujui oleh ' . auth()->user()->name . '.';
        } else {
            $type  = 'Rejected';
            $notif = 'Pengajuan Dinas Luar Anda (' . $pengajuan->tanggal_mulai . ' s/d ' . $pengajuan->tanggal_akhir . ') ditolak oleh ' . auth()->user()->name . '. Catatan: ' . ($request->catatan ?? '-');
        }

        $url = url('/pengajuan-dinas-luar');

        $karyawan->messages = [
            'user_id' => auth()->user()->id,
            'from'    => auth()->user()->name,
            'message' => $notif,
            'action'  => '/pengajuan-dinas-luar',
        ];
        $karyawan->notify(new \App\Notifications\UserNotification);

        NotifApproval::dispatch($type, $karyawan->id, $notif, $url);
        WhatsAppService::send($karyawan->telepon, $notif . "\n" . $url);

        Alert::success('Berhasil', 'Status pengajuan telah diperbarui.');
        return redirect('/data-pengajuan-dinas');
    }

    // ========================
    // ADMIN: Form input dinas luar manual (tanpa approval)
    // ========================
    public function manualForm()
    {
        $shifts = Shift::orderBy('nama_shift')->get();
        $users  = User::orderBy('name')->get();

        return view('pengajuan-dinas-luar.manual', [
            'title'  => 'Input Dinas Luar Manual',
            'shifts' => $shifts,
            'users'  => $users,
        ]);
    }

    public function manualStore(Request $request)
    {
        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'shift_id'      => 'required|exists:shifts,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_mulai',
            'lokasi'        => 'required|string|max:255',
            'alasan'        => 'nullable|string|max:500',
        ]);

        $count = 0;
        DB::transaction(function () use ($request, &$count) {
            $pengajuan = PengajuanDinasLuar::create([
                'user_id'       => $request->user_id,
                'shift_id'      => $request->shift_id,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_akhir' => $request->tanggal_akhir,
                'lokasi_tujuan' => $request->lokasi,
                'alasan'        => $request->alasan,
                'status'        => 'Approved',
                'user_approval' => auth()->user()->id,
            ]);

            $count = $this->createDinasLuarRows($pengajuan);
        });

        Alert::success('Berhasil', "Dinas luar manual berhasil ditambahkan untuk {$count} hari.");
        return redirect('/data-pengajuan-dinas');
    }

    private function hasOverlappingRequest(int $userId, string $tanggalMulai, string $tanggalAkhir): bool
    {
        return PengajuanDinasLuar::where('user_id', $userId)
            ->whereIn('status', ['Pending', 'Approved'])
            ->where('tanggal_mulai', '<=', $tanggalAkhir)
            ->where('tanggal_akhir', '>=', $tanggalMulai)
            ->exists();
    }

    private function createDinasLuarRows(PengajuanDinasLuar $pengajuan): int
    {
        $begin = new \DateTime($pengajuan->tanggal_mulai);
        $end = new \DateTime($pengajuan->tanggal_akhir);
        $end->modify('+1 day');
        $range = new \DatePeriod($begin, new \DateInterval('P1D'), $end);
        $count = 0;

        foreach ($range as $date) {
            dinasLuar::updateOrCreate(
                [
                    'user_id'  => $pengajuan->user_id,
                    'tanggal'  => $date->format('Y-m-d'),
                    'shift_id' => $pengajuan->shift_id,
                ],
                [
                    'lokasi' => $pengajuan->lokasi_tujuan,
                ]
            );
            $count++;
        }

        return $count;
    }
}
