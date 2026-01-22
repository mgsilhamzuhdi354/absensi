<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use App\Models\Lokasi;
use App\Models\Jabatan;
use App\Models\settings;
use App\Exports\AbsenExport;
use App\Models\JenisKinerja;
use App\Models\MappingShift;
use Illuminate\Http\Request;
use App\Events\NotifApproval;
use App\Models\LaporanKinerja;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class AbsenController extends Controller
{
    public function index()
    {
        date_default_timezone_set('Asia/Jakarta');
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

        if (auth()->user()->is_admin == 'admin') {
            return view('absen.index', [
                'title' => 'Absen',
                'shift_karyawan' => MappingShift::where('user_id', $user_login)->where('tanggal', $tanggal)->first()
            ]);
        } else {
            return view('absen.indexUser', [
                'title' => 'Absen',
                'shift_karyawan' => MappingShift::where('user_id', $user_login)->where('tanggal', $tanggal)->first()
            ]);
        }

    }

    public function myLocation(Request $request)
    {
        return redirect('maps/'.$request["lat"].'/'.$request['long'].'/'.$request['userid']);
    }

    public function absenMasuk(Request $request, $id)
    {
        date_default_timezone_set('Asia/Jakarta');

        $mapping_shift = MappingShift::find($id);
        
        // Cek apakah sudah absen masuk hari ini
        if ($mapping_shift->jam_absen != null) {
            Alert::error('Sudah Absen', 'Anda sudah melakukan absen masuk hari ini.');
            return redirect('/absen');
        }
        
        // Cek apakah belum waktunya absen masuk (max 30 menit sebelum jadwal)
        $shift = $mapping_shift->Shift->jam_masuk;
        $tanggal = $mapping_shift->tanggal;
        $waktu_shift = strtotime($tanggal . ' ' . $shift);
        $waktu_sekarang = time();
        $selisih_menit = ($waktu_shift - $waktu_sekarang) / 60;
        
        if ($selisih_menit > 30) {
            Alert::error('Belum Waktunya', 'Anda hanya bisa absen masuk maksimal 30 menit sebelum jadwal shift.');
            return redirect('/absen');
        }

        $lat_kantor = auth()->user()->Lokasi->lat_kantor;
        $long_kantor = auth()->user()->Lokasi->long_kantor;
        $radius = auth()->user()->Lokasi->radius;
        $nama_lokasi = auth()->user()->Lokasi->nama_lokasi;

        $request["jarak_masuk"] = $this->distance($request["lat_absen"], $request["long_absen"], $lat_kantor, $long_kantor, "K") * 1000;

        $request["jam_absen"] = date('H:i');

        if($request["jarak_masuk"] > $radius && $mapping_shift->lock_location == 1) {
            Alert::error('Diluar Jangkauan', 'Lokasi Anda Diluar Radius ' . $nama_lokasi);
            return redirect('/absen');
        } else {
            $foto_jam_absen = $request["foto_jam_absen"];

            // Validasi foto tidak kosong dan memiliki format base64 yang valid
            if (empty($foto_jam_absen) || !str_contains($foto_jam_absen, ';base64,')) {
                Alert::error('Error', 'Foto absen masuk tidak valid. Silakan ambil foto ulang.');
                return redirect('/absen');
            }

            $image_parts = explode(";base64,", $foto_jam_absen);

            if (!isset($image_parts[1])) {
                Alert::error('Error', 'Format foto tidak valid. Silakan ambil foto ulang.');
                return redirect('/absen');
            }

            $image_base64 = base64_decode($image_parts[1]);
            $fileName = 'foto_jam_absen/' . uniqid() . '.png';

            Storage::put($fileName, $image_base64);


            $request["foto_jam_absen"] = $fileName;

            $request["status_absen"] = "Masuk";

            $shift = $mapping_shift->Shift->jam_masuk;
            $tanggal = $mapping_shift->tanggal;

            $tgl_skrg = date("Y-m-d");

            $awal  = strtotime($tanggal . $shift);
            $akhir = strtotime($tgl_skrg . $request["jam_absen"]);
            $diff  = $akhir - $awal;

            if ($diff <= 0) {
                $request["telat"] = 0;
                $jenis_kinerja = JenisKinerja::where('nama', 'Presensi Kehadiran Ontime')->first();
                $laporan_kinerja_before = LaporanKinerja::where('user_id', auth()->user()->id)->latest()->first();
                if ($laporan_kinerja_before) {
                    LaporanKinerja::create([
                        'user_id' => auth()->user()->id,
                        'tanggal' => $tgl_skrg,
                        'jenis_kinerja_id' => $jenis_kinerja->id,
                        'nilai' => $jenis_kinerja->bobot,
                        'penilaian_berjalan' => $laporan_kinerja_before->penilaian_berjalan + $jenis_kinerja->bobot,
                        'reference' => 'App\Models\MappingShift',
                        'reference_id' => $mapping_shift->id,
                    ]);
                } else {
                    LaporanKinerja::create([
                        'user_id' => auth()->user()->id,
                        'tanggal' => $tgl_skrg,
                        'jenis_kinerja_id' => $jenis_kinerja->id,
                        'nilai' => $jenis_kinerja->bobot,
                        'penilaian_berjalan' => $jenis_kinerja->bobot,
                        'reference' => 'App\Models\MappingShift',
                        'reference_id' => $mapping_shift->id,
                    ]);
                }
            } else {
                $request["telat"] = $diff;
                $jenis_kinerja = JenisKinerja::where('nama', 'Telat Presensi Masuk')->first();
                $laporan_kinerja_before = LaporanKinerja::where('user_id', auth()->user()->id)->latest()->first();
                if ($laporan_kinerja_before) {
                    LaporanKinerja::create([
                        'user_id' => auth()->user()->id,
                        'tanggal' => $tgl_skrg,
                        'jenis_kinerja_id' => $jenis_kinerja->id,
                        'nilai' => $jenis_kinerja->bobot,
                        'penilaian_berjalan' => $laporan_kinerja_before->penilaian_berjalan + $jenis_kinerja->bobot,
                        'reference' => 'App\Models\MappingShift',
                        'reference_id' => $mapping_shift->id,
                    ]);
                } else {
                    LaporanKinerja::create([
                        'user_id' => auth()->user()->id,
                        'tanggal' => $tgl_skrg,
                        'jenis_kinerja_id' => $jenis_kinerja->id,
                        'nilai' => $jenis_kinerja->bobot,
                        'penilaian_berjalan' => $jenis_kinerja->bobot,
                        'reference' => 'App\Models\MappingShift',
                        'reference_id' => $mapping_shift->id,
                    ]);
                }
            }

            if ($mapping_shift->lock_location == 1) {
                $validatedData = $request->validate([
                    'jam_absen' => 'required',
                    'telat' => 'nullable',
                    'foto_jam_absen' => 'required',
                    'lat_absen' => 'required',
                    'long_absen' => 'required',
                    'jarak_masuk' => 'required',
                    'status_absen' => 'required'
                ]);
            } else {
                $validatedData = $request->validate([
                    'jam_absen' => 'required',
                    'telat' => 'nullable',
                    'foto_jam_absen' => 'required',
                    'lat_absen' => 'required',
                    'long_absen' => 'required',
                    'jarak_masuk' => 'required',
                    'keterangan_masuk' => 'required',
                    'status_absen' => 'required'
                ]);
            }

            MappingShift::where('id', $id)->update($validatedData);

            $request->session()->flash('success', 'Berhasil Absen Masuk');

            return redirect('/absen');
        }

    }

    public function absenPulang(Request $request, $id)
    {
        date_default_timezone_set('Asia/Jakarta');
        
        $mapping_shift = MappingShift::find($id);
        
        // Cek apakah sudah absen masuk
        if ($mapping_shift->jam_absen == null) {
            Alert::error('Belum Absen Masuk', 'Anda harus absen masuk terlebih dahulu.');
            return redirect('/absen');
        }
        
        // Cek apakah sudah absen pulang hari ini
        if ($mapping_shift->jam_pulang != null) {
            Alert::error('Sudah Absen', 'Anda sudah melakukan absen pulang hari ini.');
            return redirect('/absen');
        }
        
        // Cek apakah belum waktunya pulang (max 30 menit sebelum jadwal)
        $shiftmasuk = $mapping_shift->Shift->jam_masuk;
        $shiftpulang = $mapping_shift->Shift->jam_keluar;
        $tanggal = $mapping_shift->tanggal;
        
        // Handle shift malam (pulang hari berikutnya)
        $timeMasuk = strtotime($shiftmasuk);
        $timePulang = strtotime($shiftpulang);
        if ($timePulang < $timeMasuk) {
            $tanggal_pulang = date('Y-m-d', strtotime('+1 days', strtotime($tanggal)));
        } else {
            $tanggal_pulang = $tanggal;
        }
        
        $waktu_pulang = strtotime($tanggal_pulang . ' ' . $shiftpulang);
        $waktu_sekarang = time();
        $selisih_menit = ($waktu_pulang - $waktu_sekarang) / 60;
        
        if ($selisih_menit > 30) {
            $jam_boleh_pulang = date('H:i', strtotime($tanggal_pulang . ' ' . $shiftpulang . ' -30 minutes'));
            Alert::error('Belum Waktunya', 'Anda bisa pulang mulai jam ' . $jam_boleh_pulang . ' (30 menit sebelum jadwal).');
            return redirect('/absen');
        }
        
        $request["jam_pulang"] = date('H:i');

        $lat_kantor = auth()->user()->Lokasi->lat_kantor ?? null;
        $long_kantor = auth()->user()->Lokasi->long_kantor ?? null;
        $radius = auth()->user()->Lokasi->radius ?? null;
        $nama_lokasi = auth()->user()->Lokasi->nama_lokasi ?? null;

        $request["jarak_pulang"] = $this->distance($request["lat_pulang"], $request["long_pulang"], $lat_kantor, $long_kantor, "K") * 1000;

        if($request["jarak_pulang"] > $radius && $mapping_shift->lock_location == 1) {
            Alert::error('Diluar Jangkauan', 'Lokasi Anda Diluar Radius ' . $nama_lokasi);
            return redirect('/absen');
        } else {
            $foto_jam_pulang = $request["foto_jam_pulang"];

            // Validasi foto tidak kosong dan memiliki format base64 yang valid
            if (empty($foto_jam_pulang) || !str_contains($foto_jam_pulang, ';base64,')) {
                Alert::error('Error', 'Foto absen pulang tidak valid. Silakan ambil foto ulang.');
                return redirect('/absen');
            }

            $image_parts = explode(";base64,", $foto_jam_pulang);

            if (!isset($image_parts[1])) {
                Alert::error('Error', 'Format foto tidak valid. Silakan ambil foto ulang.');
                return redirect('/absen');
            }

            $image_base64 = base64_decode($image_parts[1]);
            $fileName = 'foto_jam_pulang/' . uniqid() . '.png';

            Storage::put($fileName, $image_base64);

            $request["foto_jam_pulang"] = $fileName;


            $shiftmasuk = $mapping_shift->Shift->jam_masuk;
            $shiftpulang = $mapping_shift->Shift->jam_keluar;
            $tanggal = $mapping_shift->tanggal;
            $new_tanggal = "";
            $timeMasuk = strtotime($shiftmasuk);
            $timePulang = strtotime($shiftpulang);


            if ($timePulang < $timeMasuk) {
                $new_tanggal = date('Y-m-d', strtotime('+1 days', strtotime($tanggal)));
            } else {
                $new_tanggal = $tanggal;
            }

            $tgl_skrg = date("Y-m-d");

            $akhir = strtotime($new_tanggal . $shiftpulang);
            $awal  = strtotime($tgl_skrg . $request["jam_pulang"]);
            $diff  = $akhir - $awal;

            if ($diff <= 0) {
                $request["pulang_cepat"] = 0;
                $jenis_kinerja = JenisKinerja::where('nama', 'Pulang tepat waktu')->first();
                $laporan_kinerja_before = LaporanKinerja::where('user_id', auth()->user()->id)->latest()->first();
                if ($laporan_kinerja_before) {
                    LaporanKinerja::create([
                        'user_id' => auth()->user()->id,
                        'tanggal' => $tgl_skrg,
                        'jenis_kinerja_id' => $jenis_kinerja->id,
                        'nilai' => $jenis_kinerja->bobot,
                        'penilaian_berjalan' => $laporan_kinerja_before->penilaian_berjalan + $jenis_kinerja->bobot,
                        'reference' => 'App\Models\MappingShift',
                        'reference_id' => $mapping_shift->id,
                    ]);
                } else {
                    LaporanKinerja::create([
                        'user_id' => auth()->user()->id,
                        'tanggal' => $tgl_skrg,
                        'jenis_kinerja_id' => $jenis_kinerja->id,
                        'nilai' => $jenis_kinerja->bobot,
                        'penilaian_berjalan' => $jenis_kinerja->bobot,
                        'reference' => 'App\Models\MappingShift',
                        'reference_id' => $mapping_shift->id,
                    ]);
                }
            } else {
                $request["pulang_cepat"] = $diff;
                $jenis_kinerja = JenisKinerja::where('nama', 'Pulang Sebelum waktunya')->first();
                $laporan_kinerja_before = LaporanKinerja::where('user_id', auth()->user()->id)->latest()->first();
                if ($laporan_kinerja_before) {
                    LaporanKinerja::create([
                        'user_id' => auth()->user()->id,
                        'tanggal' => $tgl_skrg,
                        'jenis_kinerja_id' => $jenis_kinerja->id,
                        'nilai' => $jenis_kinerja->bobot,
                        'penilaian_berjalan' => $laporan_kinerja_before->penilaian_berjalan + $jenis_kinerja->bobot,
                        'reference' => 'App\Models\MappingShift',
                        'reference_id' => $mapping_shift->id,
                    ]);
                } else {
                    LaporanKinerja::create([
                        'user_id' => auth()->user()->id,
                        'tanggal' => $tgl_skrg,
                        'jenis_kinerja_id' => $jenis_kinerja->id,
                        'nilai' => $jenis_kinerja->bobot,
                        'penilaian_berjalan' => $jenis_kinerja->bobot,
                        'reference' => 'App\Models\MappingShift',
                        'reference_id' => $mapping_shift->id,
                    ]);
                }
            }

            if ($mapping_shift->lock_location == 1) {
                $validatedData = $request->validate([
                    'jam_pulang' => 'required',
                    'foto_jam_pulang' => 'required',
                    'lat_pulang' => 'required',
                    'long_pulang' => 'required',
                    'pulang_cepat' => 'required',
                    'jarak_pulang' => 'required'
                ]);
            } else {
                $validatedData = $request->validate([
                    'jam_pulang' => 'required',
                    'foto_jam_pulang' => 'required',
                    'lat_pulang' => 'required',
                    'long_pulang' => 'required',
                    'pulang_cepat' => 'required',
                    'keterangan_pulang' => 'required',
                    'jarak_pulang' => 'required'
                ]);
            }

            MappingShift::where('id', $id)->update($validatedData);

            return redirect('/absen')->with('success', 'Berhasil Absen Pulang');
        }
    }

    public function distance($lat1, $lon1, $lat2, $lon2, $unit)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }

    public function dataAbsen()
    {
        date_default_timezone_set('Asia/Jakarta');
        $data_absen = MappingShift::dataAbsen()->paginate(10)->withQueryString();

        return view('absen.dataabsen', [
            'title' => 'Data Absen',
            'user' => User::select('id', 'name')->get(),
            'data_absen' => $data_absen
        ]);
    }

    public function exportDataAbsen()
    {
        return (new AbsenExport($_GET))->download('List Absensi.xlsx');
    }

    public function maps($lat, $long, $userid)
    {
        date_default_timezone_set('Asia/Jakarta');
        if (auth()->user()->is_admin == 'admin') {
            return view('absen.maps', [
                'title' => 'Maps',
                'lat' => $lat,
                'long' => $long,
                'data_user' => User::findOrFail($userid)
            ]);
        } else {
            return view('absen.mapsUser', [
                'title' => 'Maps',
                'lat' => $lat,
                'long' => $long,
                'data_user' => User::findOrFail($userid)
            ]);
        }
    }

    public function editMasuk($id)
    {
        $mapping_shift = MappingShift::findOrFail($id);
        $user = User::findOrFail($mapping_shift->user_id);
        $lokasi = $user->Lokasi;
        return view('absen.editmasuk', [
            'title' => 'Edit Absen Masuk',
            'data_absen' => $mapping_shift,
            'lokasi_kantor' => $lokasi
        ]);
    }

    public function prosesEditMasuk(Request $request, $id)
    {
        date_default_timezone_set('Asia/Jakarta');

        $mapping_shift = MappingShift::where('id', $id)->get();

        foreach ($mapping_shift as $mp) {
            $shift = $mp->Shift->jam_masuk;
            $tanggal = $mp->tanggal;
            $user_id = $mp->user_id;
        }

        $awal  = strtotime($tanggal . $shift);
        $akhir = strtotime($tanggal . $request["jam_absen"]);
        $diff  = $akhir - $awal;

        if ($diff <= 0) {
            $request["telat"] = 0;
        } else {
            $request["telat"] = $diff;
        }

        $user = User::findOrFail($user_id);
        $lat_kantor = $user->Lokasi->lat_kantor;
        $long_kantor = $user->Lokasi->long_kantor;

        $request["jarak_masuk"] = $this->distance($request["lat_absen"], $request["long_absen"], $lat_kantor, $long_kantor, "K") * 1000;

        $validatedData = $request->validate([
            'jam_absen' => 'required',
            'telat' => 'nullable',
            'foto_jam_absen' => 'image|max:5000',
            'lat_absen' => 'required',
            'long_absen' => 'required',
            'jarak_masuk' => 'required',
            'status_absen' => 'required'
        ]);

        if ($request->file('foto_jam_absen')) {
            if ($request->foto_jam_absen_lama) {
                Storage::delete($request->foto_jam_absen_lama);
            }
            $validatedData['foto_jam_absen'] = $request->file('foto_jam_absen')->store('foto_jam_absen');
        }

        MappingShift::where('id', $id)->update($validatedData);
        return redirect('/data-absen')->with('success', 'Berhasil Edit Absen Masuk (Manual)');
    }

    public function editPulang($id)
    {
        $mapping_shift = MappingShift::findOrFail($id);
        $user = User::findOrFail($mapping_shift->user_id);
        $lokasi = $user->Lokasi;
        return view('absen.editpulang', [
            'title' => 'Edit Absen Pulang',
            'data_absen' => $mapping_shift,
            'lokasi_kantor' => $lokasi
        ]);
    }

    public function prosesEditPulang(Request $request, $id)
    {
        $mapping_shift = MappingShift::where('id', $id)->get();
        foreach ($mapping_shift as $mp) {
            $shiftmasuk = $mp->Shift->jam_masuk;
            $shiftpulang = $mp->Shift->jam_keluar;
            $tanggal = $mp->tanggal;
            $user_id = $mp->user_id;
        }
        $new_tanggal = "";
        $timeMasuk = strtotime($shiftmasuk);
        $timePulang = strtotime($shiftpulang);


        if ($timePulang < $timeMasuk) {
            $new_tanggal = date('Y-m-d', strtotime('+1 days', strtotime($tanggal)));
        } else {
            $new_tanggal = $tanggal;
        }

        $akhir = strtotime($new_tanggal . $shiftpulang);
        $awal  = strtotime($new_tanggal . $request["jam_pulang"]);
        $diff  = $akhir - $awal;

        if ($diff <= 0) {
            $request["pulang_cepat"] = 0;
        } else {
            $request["pulang_cepat"] = $diff;
        }

        $user = User::findOrFail($user_id);
        $lat_kantor = $user->Lokasi->lat_kantor;
        $long_kantor = $user->Lokasi->long_kantor;

        $request["jarak_pulang"] = $this->distance($request["lat_pulang"], $request["long_pulang"], $lat_kantor, $long_kantor, "K") * 1000;

        $validatedData = $request->validate([
            'jam_pulang' => 'required',
            'foto_jam_pulang' => 'image|max:5000',
            'lat_pulang' => 'required',
            'long_pulang' => 'required',
            'pulang_cepat' => 'required',
            'jarak_pulang' => 'required'
        ]);

        if ($request->file('foto_jam_pulang')) {
            if ($request->foto_jam_pulang_lama) {
                Storage::delete($request->foto_jam_pulang_lama);
            }
            $validatedData['foto_jam_pulang'] = $request->file('foto_jam_pulang')->store('foto_jam_pulang');
        }

        MappingShift::where('id', $id)->update($validatedData);

        return redirect('/data-absen')->with('success', 'Berhasil Edit Absen Pulang (Manual)');
    }

    public function deleteAdmin($id)
    {
        $delete = MappingShift::find($id);
        Storage::delete($delete->foto_jam_absen);
        Storage::delete($delete->foto_jam_pulang);
        $delete->delete();
        return redirect('/data-absen')->with('success', 'Data Berhasil di Delete');
    }

    public function myAbsen(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $tglskrg = date('Y-m-d');
        $data_absen = MappingShift::where('tanggal', $tglskrg)->where('user_id', auth()->user()->id);

        if($request["mulai"] == null) {
            $request["mulai"] = $request["akhir"];
        }

        if($request["akhir"] == null) {
            $request["akhir"] = $request["mulai"];
        }

        if ($request["mulai"] && $request["akhir"]) {
            $data_absen = MappingShift::where('user_id', auth()->user()->id)->whereBetween('tanggal', [$request["mulai"], $request["akhir"]]);
        }

        if (auth()->user()->is_admin == 'admin') {
            return view('absen.myabsen', [
                'title' => 'My Absen',
                'data_absen' => $data_absen->paginate(10)->withQueryString()
            ]);
        } else {
            return view('absen.myabsenuser', [
                'title' => 'My Absen',
                'data_absen' => $data_absen->paginate(10)->withQueryString()
            ]);
        }
    }

    public function pengajuan($id)
    {
        $ms = MappingShift::find($id);
        $title = 'Pengajuan Absensi';
        return view('absen.pengajuan', compact(
            'ms',
            'title'
        ));
    }

    public function pengajuanProses(Request $request, $id)
    {
        $ms = MappingShift::find($id);
        $validated = $request->validate([
            'jam_masuk_pengajuan' => 'required',
            'jam_pulang_pengajuan' => 'required',
            'deskripsi' => 'required',
            'file_pengajuan' => 'required',
            'status_pengajuan' => 'required',
        ]);

        if ($request->file('file_pengajuan')) {
            $validated['file_pengajuan'] = $request->file('file_pengajuan')->store('file_pengajuan');
        }

        $ms->update($validated);

        $jabatan = Jabatan::find(auth()->user()->jabatan_id);
        $user = User::find($jabatan->manager);

        $type = 'Approval';
        $notif = 'Pengajuan Absensi Dari ' . auth()->user()->name . ' Butuh Approval Anda';
        $url = url('/pengajuan-absensi/edit/'.$ms->id);

        $user->messages = [
            'user_id'   =>  auth()->user()->id,
            'from'   =>  auth()->user()->name,
            'message'   =>  $notif,
            'action'   =>  '/pengajuan-absensi/edit/'.$ms->id
        ];
        $user->notify(new \App\Notifications\UserNotification);

        NotifApproval::dispatch($type, $user->id, $notif, $url);

        WhatsAppService::send($user->telepon, $notif . "\n" . $url);

        return redirect('/pengajuan-absensi')->with('success', 'Pengajuan Berhasil Disimpan');
    }

    public function pengajuanAbsensi()
    {
        $title = 'Pengajuan Absensi';
        $search = request()->input('search');
        $jabatan = Jabatan::find(auth()->user()->jabatan_id);
        $user_id = User::where('jabatan_id', auth()->user()->jabatan_id)->pluck('id');
        $mapping_shift = MappingShift::where('status_pengajuan', '!=', null)
                                    ->when($jabatan->manager == auth()->user()->id, function ($query) use ($user_id) {
                                        $query->where(function ($q) use ($user_id) {
                                            $q->whereIn('user_id', $user_id)
                                                ->orWhere('user_id', auth()->user()->id);
                                        });
                                    })
                                    ->when($jabatan->manager !== auth()->user()->id, function ($query) {
                                        $query->where('user_id', auth()->user()->id);
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('User', function ($query) use ($search) {
                                            $query->where('name', 'LIKE', '%'.$search.'%');
                                        });
                                    })
                                    ->orderBy('tanggal', 'DESC')
                                    ->paginate(10)
                                    ->withQueryString();

        return view('absen.indexPengajuan', compact(
            'mapping_shift',
            'title'
        ));
    }

    public function editPengajuanAbsensi($id)
    {
        $ms = MappingShift::find($id);
        $jabatan = Jabatan::find(auth()->user()->jabatan_id);
        $title = 'Pengajuan Absensi';
        return view('absen.editPengajuan', compact(
            'ms',
            'jabatan',
            'title'
        ));
    }

    public function updatePengajuanAbsensi(Request $request, $id)
    {
        $ms = MappingShift::find($id);
        $validated = $request->validate([
            'jam_masuk_pengajuan' => 'required',
            'jam_pulang_pengajuan' => 'required',
            'deskripsi' => 'required',
            'file_pengajuan' => 'nullable',
            'komentar' => 'nullable',
            'status_pengajuan' => 'required',
        ]);

        $ms->update($validated);

        if ($request['status_pengajuan'] == 'Disetujui') {
            $shiftmasuk = $ms->Shift->jam_masuk;
            $tanggal = $ms->tanggal;

            $awal_masuk  = strtotime($tanggal . $shiftmasuk);
            $akhir_masuk = strtotime($tanggal . $ms->jam_masuk_pengajuan);
            $diff_masuk  = $akhir_masuk - $awal_masuk;

            if ($diff_masuk <= 0) {
                $telat = 0;
            } else {
                $telat = $diff_masuk;
            }

            $shiftpulang = $ms->Shift->jam_keluar;
            $new_tanggal = "";
            $timeMasuk = strtotime($shiftmasuk);
            $timePulang = strtotime($shiftpulang);

            if ($timePulang < $timeMasuk) {
                $new_tanggal = date('Y-m-d', strtotime('+1 days', strtotime($tanggal)));
            } else {
                $new_tanggal = $tanggal;
            }

            $akhir_pulang = strtotime($new_tanggal . $shiftpulang);
            $awal_pulang  = strtotime($new_tanggal . $ms->jam_pulang_pengajuan);
            $diff_pulang  = $akhir_pulang - $awal_pulang;

            if ($diff_pulang <= 0) {
                $pulang_cepat = 0;
            } else {
                $pulang_cepat = $diff_pulang;
            }

            $ms->update([
                'jam_absen' => $ms->jam_masuk_pengajuan,
                'telat' => $telat,
                'lat_absen' => $ms->User->Lokasi->lat_kantor,
                'long_absen' => $ms->User->Lokasi->long_kantor,
                'jarak_masuk' => 0,
                'jam_pulang' => $ms->jam_pulang_pengajuan,
                'pulang_cepat' => $pulang_cepat,
                'lat_pulang' => $ms->User->Lokasi->lat_kantor,
                'long_pulang' => $ms->User->Lokasi->long_kantor,
                'jarak_pulang' => 0,
                'status_absen' => 'Masuk',
            ]);

            $user = User::find($ms->user_id);

            $type = 'Approved';
            $notif = 'Pengajuan Absensi Anda Telah Di Setujui Oleh ' . auth()->user()->name;
            $url = url('/pengajuan-absensi/edit/'.$ms->id);

            $user->messages = [
                'user_id'   =>  auth()->user()->id,
                'from'   =>  auth()->user()->name,
                'message'   =>  $notif,
                'action'   =>  '/pengajuan-absensi/edit/'.$ms->id
            ];
            $user->notify(new \App\Notifications\UserNotification);

            NotifApproval::dispatch($type, $user->id, $notif, $url);

            WhatsAppService::send($user->telepon, $notif . "\n" . $url);
        } else if ($request['status_pengajuan'] == 'Tidak Disejutui') {
            $user = User::find($ms->user_id);

            $type = 'Rejected';
            $notif = 'Pengajuan Absensi Anda Tidak Setujui Oleh ' . auth()->user()->name;
            $url = url('/pengajuan-absensi/edit/'.$ms->id);

            $user->messages = [
                'user_id'   =>  auth()->user()->id,
                'from'   =>  auth()->user()->name,
                'message'   =>  $notif,
                'action'   =>  '/pengajuan-absensi/edit/'.$ms->id
            ];
            $user->notify(new \App\Notifications\UserNotification);

            NotifApproval::dispatch($type, $user->id, $notif, $url);
        } else {
            $jabatan = Jabatan::find(auth()->user()->jabatan_id);
            $user = User::find($jabatan->manager);

            $type = 'Approval';
            $notif = 'Pengajuan Absensi Dari ' . auth()->user()->name . ' Butuh Approval Anda';
            $url = url('/pengajuan-absensi/edit/'.$ms->id);

            $user->messages = [
                'user_id'   =>  auth()->user()->id,
                'from'   =>  auth()->user()->name,
                'message'   =>  $notif,
                'action'   =>  '/pengajuan-absensi/edit/'.$ms->id
            ];
            $user->notify(new \App\Notifications\UserNotification);

            NotifApproval::dispatch($type, $user->id, $notif, $url);

            WhatsAppService::send($user->telepon, $notif . "\n" . $url);
        }

        return redirect('/pengajuan-absensi')->with('success', 'Pengajuan Berhasil Diupdate');
    }

}
