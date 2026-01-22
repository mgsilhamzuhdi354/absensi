<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\LaporanKinerja;
use App\Models\JenisKinerja;

class KinerjaPegawaiController extends Controller
{
    public function index()
    {
        $title = 'Laporan Kinerja';
        $search = request()->input('search');
        $users = User::when($search, function ($query) use ($search) {
                        return $query->where('name', 'LIKE', '%' . $search . '%');
                    })
                    ->orderBy('name', 'ASC')
                    ->paginate(10)
                    ->withQueryString();

        $jenis_kinerja = JenisKinerja::all();

        return view('kinerja-pegawai.index', compact(
            'title',
            'users',
            'jenis_kinerja'
        ));
    }

    public function storeManual(Request $request) 
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'jenis_kinerja_id' => 'required|exists:jenis_kinerjas,id',
            'nilai' => 'required|numeric',
            'tanggal' => 'required|date',
        ]);

        date_default_timezone_set('Asia/Jakarta');
        
        $laporan_kinerja_before = LaporanKinerja::where('user_id', $request->user_id)->latest()->first();
        $penilaian_berjalan = $laporan_kinerja_before ? $laporan_kinerja_before->penilaian_berjalan : 0;
        
        $new_penilaian = $penilaian_berjalan + $request->nilai;
        
        LaporanKinerja::create([
            'user_id' => $request->user_id,
            'tanggal' => $request->tanggal,
            'jenis_kinerja_id' => $request->jenis_kinerja_id,
            'nilai' => $request->nilai,
            'penilaian_berjalan' => $new_penilaian,
            'reference' => 'Manual Adjustment',
            'reference_id' => auth()->user()->id, // Admin who did it
        ]);

        return redirect()->back()->with('success', 'Poin Kinerja Berhasil Ditambahkan');
    }

    /**
     * Show form to edit a specific point entry
     */
    public function edit($id)
    {
        $laporan = LaporanKinerja::with(['jenis', 'user'])->findOrFail($id);
        $jenis_kinerja = JenisKinerja::all();
        $title = 'Edit Poin Kinerja';
        
        return view('kinerja-pegawai.edit', compact('laporan', 'jenis_kinerja', 'title'));
    }

    /**
     * Update a specific point entry and recalculate running totals
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nilai' => 'required|numeric',
            'jenis_kinerja_id' => 'required|exists:jenis_kinerjas,id',
        ]);

        $laporan = LaporanKinerja::findOrFail($id);
        
        // Update the record
        $laporan->update([
            'nilai' => $request->nilai,
            'jenis_kinerja_id' => $request->jenis_kinerja_id,
        ]);
        
        // Recalculate all running totals for this user
        $this->recalculateUserPoints($laporan->user_id);
        
        return redirect('/kinerja-pegawai')->with('success', 'Poin Kinerja Berhasil Diupdate');
    }

    /**
     * Delete a specific point entry and recalculate running totals
     */
    public function destroy($id)
    {
        $laporan = LaporanKinerja::findOrFail($id);
        $userId = $laporan->user_id;
        
        $laporan->delete();
        
        // Recalculate all running totals for this user
        $this->recalculateUserPoints($userId);
        
        return redirect()->back()->with('success', 'Poin Kinerja Berhasil Dihapus');
    }

    /**
     * Recalculate all penilaian_berjalan values for a user
     * This ensures consistency after edits or deletions
     */
    private function recalculateUserPoints($userId)
    {
        // Get all points for user ordered by date and ID
        $points = LaporanKinerja::where('user_id', $userId)
            ->orderBy('tanggal', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();
        
        $runningTotal = 0;
        foreach ($points as $point) {
            $runningTotal += $point->nilai;
            $point->update(['penilaian_berjalan' => $runningTotal]);
        }
    }

    /**
     * Show detail history for a specific user
     */
    public function history($userId)
    {
        $user = User::findOrFail($userId);
        $title = 'Riwayat Poin: ' . $user->name;
        $laporan = LaporanKinerja::where('user_id', $userId)
            ->with('jenis')
            ->orderBy('id', 'DESC')
            ->paginate(20);
        $jenis_kinerja = JenisKinerja::all();
        
        return view('kinerja-pegawai.history', compact('user', 'laporan', 'title', 'jenis_kinerja'));
    }

    public function indexUser()
    {
        $title = 'Laporan Kinerja';
        $skor_akhir = LaporanKinerja::where('user_id', auth()->user()->id)->latest()->first();
        $list_penilaian = LaporanKinerja::where('user_id', auth()->user()->id)->orderBy('id', 'DESC')->paginate(10);
        $data_penilaian = LaporanKinerja::selectRaw('jenis_kinerjas.nama AS nama, COALESCE(SUM(laporan_kinerjas.nilai), 0) AS total_penilaian')
                                        ->rightJoin('jenis_kinerjas', function($join) {
                                            $join->on('jenis_kinerjas.id', '=', 'laporan_kinerjas.jenis_kinerja_id')
                                                ->where('user_id', auth()->user()->id);
                                        })
                                        ->groupBy('nama')
                                        ->get();

        return view('kinerja-pegawai.indexUser', compact(
            'title',
            'skor_akhir',
            'list_penilaian',
            'data_penilaian',
        ));
    }

    /**
     * Delete a point entry by the user themselves
     */
    public function destroyUser($id)
    {
        $laporan = LaporanKinerja::where('id', $id)
            ->where('user_id', auth()->user()->id)
            ->firstOrFail();
        
        $userId = $laporan->user_id;
        $laporan->delete();
        
        // Recalculate all running totals for this user
        $this->recalculateUserPoints($userId);
        
        return redirect()->back()->with('success', 'Poin berhasil dihapus');
    }

    /**
     * Get KPI detail by category (AJAX)
     */
    public function detailKategori(Request $request)
    {
        $kategori = $request->input('kategori');
        
        // Get jenis_kinerja by name
        $jenisKinerja = JenisKinerja::where('nama', $kategori)->first();
        
        if (!$jenisKinerja) {
            return response()->json(['data' => [], 'total' => 0]);
        }
        
        // Get all laporan for this user and kategori
        $data = LaporanKinerja::where('user_id', auth()->user()->id)
            ->where('jenis_kinerja_id', $jenisKinerja->id)
            ->orderBy('tanggal', 'DESC')
            ->get(['id', 'tanggal', 'nilai', 'reference', 'reference_id']);
        
        $total = $data->sum('nilai');
        
        return response()->json([
            'data' => $data,
            'total' => $total
        ]);
    }

}
