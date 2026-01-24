<?php

namespace App\Http\Controllers;
use App\Models\Cuti;
use App\Models\User;
use App\Models\Berita;
use App\Models\Kasbon;
use App\Models\Lembur;
use App\Models\Payroll;
use App\Models\ResetCuti;
use App\Models\MappingShift;
use Illuminate\Http\Request;
use App\Models\Reimbursement;
use App\Models\LaporanKinerja;
use App\Models\JenisKinerja;

class dashboardController extends Controller
{
    public function index()
    {
        date_default_timezone_set('Asia/Jakarta');
        $tgl_skrg = date("Y-m-d");
        $tahun_skrg = date('Y');
        $bulan_skrg = date('m');
        $jmlh_bulan = cal_days_in_month(CAL_GREGORIAN,$bulan_skrg,$tahun_skrg);
        $tgl_mulai = date('Y-m-01');
        $tgl_akhir = date('Y-m-'.$jmlh_bulan);

        if(auth()->user()->is_admin == "admin" && session('dashboard_view') !== 'user'){
            // KPI Data - Top 10 Performers (berdasarkan total nilai kinerja bulan ini)
            $top_performers = User::with(['jabatan'])
                ->get()
                ->map(function($user) use ($tgl_mulai, $tgl_akhir) {
                    // Hitung total nilai kinerja bulan ini
                    $kpi_score = LaporanKinerja::where('user_id', $user->id)
                        ->whereBetween('tanggal', [$tgl_mulai, $tgl_akhir])
                        ->sum('nilai');
                    $user->kpi_score = $kpi_score ?? 0;
                    return $user;
                })
                ->sortByDesc('kpi_score')
                ->take(10)
                ->values();
            
            // KPI Distribution by Category (berdasarkan total nilai kinerja bulan ini)
            // Hanya hitung user yang MEMILIKI data kinerja
            $kpi_buruk = 0;
            $kpi_cukup = 0;
            $kpi_lumayan = 0;
            $kpi_baik = 0;
            $kpi_belum_ada_data = 0;
            
            $all_users = User::all();
            foreach($all_users as $user) {
                // Cek apakah user memiliki data kinerja bulan ini
                $has_kinerja = LaporanKinerja::where('user_id', $user->id)
                    ->whereBetween('tanggal', [$tgl_mulai, $tgl_akhir])
                    ->exists();
                
                if(!$has_kinerja) {
                    $kpi_belum_ada_data++;
                    continue;
                }
                
                // Hitung total nilai kinerja bulan ini untuk setiap user
                $score = LaporanKinerja::where('user_id', $user->id)
                    ->whereBetween('tanggal', [$tgl_mulai, $tgl_akhir])
                    ->sum('nilai') ?? 0;
                
                if($score <= 0) {
                    $kpi_buruk++;
                } elseif($score <= 50) {
                    $kpi_cukup++;
                } elseif($score <= 100) {
                    $kpi_lumayan++;
                } else {
                    $kpi_baik++;
                }
            }
            
            // Monthly KPI Trend - Last 6 months (rata-rata total nilai per user per bulan)
            $kpi_trend = [];
            for($i = 5; $i >= 0; $i--) {
                $month_start = date('Y-m-01', strtotime("-$i months"));
                $month_end = date('Y-m-t', strtotime("-$i months"));
                $month_name = date('M Y', strtotime("-$i months"));
                
                // Hitung rata-rata total nilai per user untuk bulan ini
                $total_score = LaporanKinerja::whereBetween('tanggal', [$month_start, $month_end])
                    ->sum('nilai');
                $user_count = LaporanKinerja::whereBetween('tanggal', [$month_start, $month_end])
                    ->distinct('user_id')
                    ->count('user_id');
                
                $avg_score = $user_count > 0 ? ($total_score / $user_count) : 0;
                
                $kpi_trend[] = [
                    'month' => $month_name,
                    'score' => round($avg_score, 2)
                ];
            }
            
            // KPI by Jenis Kinerja (semua jenis, termasuk yang belum ada datanya)
            $kpi_by_jenis = JenisKinerja::leftJoin('laporan_kinerjas', function($join) use ($tgl_mulai, $tgl_akhir) {
                    $join->on('jenis_kinerjas.id', '=', 'laporan_kinerjas.jenis_kinerja_id')
                        ->whereBetween('laporan_kinerjas.tanggal', [$tgl_mulai, $tgl_akhir]);
                })
                ->selectRaw('jenis_kinerjas.nama AS nama, COALESCE(SUM(laporan_kinerjas.nilai), 0) AS total')
                ->groupBy('jenis_kinerjas.id', 'jenis_kinerjas.nama')
                ->get();

            return view('dashboard.index', [
                'title' => 'Dashboard',
                'jumlah_user' => User::count(),
                'jumlah_masuk' => MappingShift::where('tanggal', $tgl_skrg)->where('status_absen', 'Masuk')->count(),
                'jumlah_libur' => MappingShift::where('tanggal', $tgl_skrg)->where('status_absen', 'Libur')->count(),
                'jumlah_cuti' => MappingShift::where('tanggal', $tgl_skrg)->where('status_absen', 'Cuti')->count(),
                'jumlah_sakit' => MappingShift::where('tanggal', $tgl_skrg)->where('status_absen', 'Sakit')->count(),
                'jumlah_izin_masuk' => MappingShift::where('tanggal', $tgl_skrg)->where('status_absen', 'Izin Masuk')->count(),
                'jumlah_izin_telat' => MappingShift::where('tanggal', $tgl_skrg)->where('status_absen', 'Izin Telat')->count(),
                'jumlah_izin_pulang_cepat' => MappingShift::where('tanggal', $tgl_skrg)->where('status_absen', 'Izin Pulang Cepat')->count(),
                'jumlah_karyawan_lembur' => Lembur::where('tanggal', $tgl_skrg)->count(),
                'payroll' => Payroll::where('bulan', date('m'))->where('tahun', date('Y'))->sum('grand_total'),
                'kasbon' => Kasbon::whereBetween('tanggal', [$tgl_mulai, $tgl_akhir])->where('status', 'Acc')->sum('nominal'),
                'reimbursement' => Reimbursement::whereBetween('tanggal', [$tgl_mulai, $tgl_akhir])->where('status', 'Approved')->sum('total'),
                // KPI Data
                'top_performers' => $top_performers,
                'kpi_buruk' => $kpi_buruk,
                'kpi_cukup' => $kpi_cukup,
                'kpi_lumayan' => $kpi_lumayan,
                'kpi_baik' => $kpi_baik,
                'kpi_belum_ada_data' => $kpi_belum_ada_data,
                'kpi_trend' => $kpi_trend,
                'kpi_by_jenis' => $kpi_by_jenis,
            ]);
        } else {
            $user_login = auth()->user()->id;
            $tanggal = "";
            $tglskrg = date('Y-m-d');
            $tglkmrn = date('Y-m-d', strtotime('-1 days'));
            $mapping_shift = MappingShift::where('user_id', $user_login)->where('tanggal', $tglkmrn)->get();
            if($mapping_shift->count() > 0) {
                foreach($mapping_shift as $mp) {
                    $jam_absen = $mp->jam_absen;
                    $jam_pulang = $mp->jam_pulang;
                }
            } else {
                $jam_absen = "-";
                $jam_pulang = "-";
            }
            if($jam_absen != null && $jam_pulang == null) {
                $tanggal = $tglkmrn;
            } else {
                $tanggal = $tglskrg;
            }

            $berita = Berita::where('tipe', 'Berita')->orderBy('id', 'DESC')->limit(4)->get();
            $informasi = Berita::where('tipe', 'Informasi')->orderBy('id', 'DESC')->limit(4)->get();
            
            // Data Kinerja / KPI
            $skor_kinerja = LaporanKinerja::where('user_id', $user_login)->latest()->first();
            $total_nilai_kinerja = LaporanKinerja::where('user_id', $user_login)
                                    ->whereBetween('tanggal', [$tgl_mulai, $tgl_akhir])
                                    ->sum('nilai');
            $data_kinerja = LaporanKinerja::selectRaw('jenis_kinerjas.nama AS nama, COALESCE(SUM(laporan_kinerjas.nilai), 0) AS total_penilaian')
                                ->rightJoin('jenis_kinerjas', function($join) use ($user_login) {
                                    $join->on('jenis_kinerjas.id', '=', 'laporan_kinerjas.jenis_kinerja_id')
                                        ->where('user_id', $user_login);
                                })
                                ->groupBy('nama')
                                ->get();
            
            return view('dashboard.indexUser', [
                'title' => 'Dashboard',
                'berita' => $berita,
                'informasi' => $informasi,
                'shift_karyawan' => MappingShift::where('user_id', $user_login)->where('tanggal', $tanggal)->first(),
                'skor_kinerja' => $skor_kinerja,
                'total_nilai_kinerja' => $total_nilai_kinerja,
                'data_kinerja' => $data_kinerja,
            ]);
        }
    }

    public function menu()
    {
        return view('dashboard.menu', [
            'title' => 'All Menu',
        ]);
    }

    /**
     * Get KPI detail by category for admin (AJAX)
     */
    public function kpiDetailAdmin(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $tahun_skrg = date('Y');
        $bulan_skrg = date('m');
        $jmlh_bulan = cal_days_in_month(CAL_GREGORIAN, $bulan_skrg, $tahun_skrg);
        $tgl_mulai = date('Y-m-01');
        $tgl_akhir = date('Y-m-' . $jmlh_bulan);
        
        $kategori = $request->input('kategori');
        
        // Get jenis_kinerja by name
        $jenisKinerja = JenisKinerja::where('nama', $kategori)->first();
        
        if (!$jenisKinerja) {
            return response()->json(['data' => [], 'total' => 0]);
        }
        
        // Get all laporan for this kategori within this month
        $data = LaporanKinerja::where('jenis_kinerja_id', $jenisKinerja->id)
            ->whereBetween('tanggal', [$tgl_mulai, $tgl_akhir])
            ->with('user:id,name')
            ->orderBy('tanggal', 'DESC')
            ->get(['id', 'user_id', 'tanggal', 'nilai', 'reference', 'reference_id'])
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'user_name' => $item->user->name ?? '-',
                    'tanggal' => $item->tanggal,
                    'nilai' => $item->nilai,
                    'reference' => $item->reference,
                    'reference_id' => $item->reference_id,
                ];
            });
        
        $total = $data->sum('nilai');
        
        return response()->json([
            'data' => $data,
            'total' => $total
        ]);
    }
}