<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Lokasi;
use App\Models\Jabatan;
use App\Models\Golongan;
use Illuminate\Support\Str;
use App\Mail\ForgotPassword;
use App\Models\JenisKinerja;
use App\Models\MappingShift;
use App\Models\settings;
use Illuminate\Http\Request;
use App\Models\LaporanKinerja;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use RealRashid\SweetAlert\Facades\Alert;

class authController extends Controller
{
    public function index()
    {
        return view('auth.login', [
            "title" => "Log In"
        ]);
    }

    public function loginAdmin()
    {
        return view('auth.loginAdmin', [
            "title" => "Log In"
        ]);
    }

    public function getStarted()
    {
        return view('auth.getStarted', [
            "title" => "Log In"
        ]);
    }

    public function welcome()
    {
        return view('auth.welcome', [
            "title" => "Log In"
        ]);
    }

    // Public Attendance - Face Recognition
    public function attendanceFace()
    {
        // Get users with face descriptors directly
        $allUsers = User::select('id', 'name', 'username', 'face_descriptor')->get();

        $usersWithFace = $allUsers->filter(function ($user) {
            return !empty($user->face_descriptor) && strlen($user->face_descriptor) > 100;
        })->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'face_descriptor' => $user->face_descriptor
            ];
        })->values();

        return view('auth.attendance-face', [
            "title" => "Absensi Face Recognition",
            "faceUsers" => $usersWithFace,
        ]);
    }

    // Get all users with face descriptors for auto-detect
    public function getFaceDescriptors()
    {
        try {
            // Get ALL users and filter in PHP
            $allUsers = User::select('id', 'name', 'username', 'nip', 'face_descriptor')->get();

            $users = $allUsers->filter(function ($user) {
                return !empty($user->face_descriptor) && strlen($user->face_descriptor) > 100;
            })->values();

            return response()->json([
                'success' => true,
                'count' => $users->count(),
                'total_users' => $allUsers->count(),
                'users' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'users' => []
            ]);
        }
    }

    // Public Attendance - QR Code
    public function attendanceQr()
    {
        return view('auth.attendance-qr', [
            "title" => "Absensi QR Code",
        ]);
    }

    public function register()
    {
        return view('auth.register', [
            "title" => "Register Account",
            "data_jabatan" => Jabatan::all(),
            "golongan" => Golongan::all(),
            "data_lokasi" => Lokasi::where('status', 'approved')->where('keterangan', 'Office')->get()
        ]);
    }

    public function presensi()
    {
        return view('auth.presensi', [
            "title" => "Presensi Masuk",
        ]);
    }

    public function presensiPulang()
    {
        return view('auth.presensiPulang', [
            "title" => "Presensi Pulang",
        ]);
    }

    public function qrMasuk()
    {
        return view('auth.qrMasuk', [
            "title" => "Presensi QR Code Masuk",
        ]);
    }

    public function qrPulang()
    {
        return view('auth.qrPulang', [
            "title" => "Presensi QR Code Pulang",
        ]);
    }

    public function qrMasukStore(Request $request)
    {
        // Check IP restriction
        $ipCheck = AttendanceSecurityController::checkIpRestriction();
        if (!$ipCheck['allowed']) {
            return response()->json(['status' => 'ip_blocked', 'message' => $ipCheck['message']]);
        }

        date_default_timezone_set('Asia/Jakarta');
        $currentDate = date('Y-m-d');
        $user = User::where('username', $request['username'])->first();
        if ($user) {
            $ms = MappingShift::where('user_id', $user->id)->where('tanggal', $currentDate)->first();
            if ($ms) {
                if ($ms->jam_absen == null) {
                    // Check if Shift relation exists
                    if (!$ms->Shift) {
                        return response()->json(['status' => 'error', 'message' => 'Data shift tidak ditemukan. Hubungi admin.']);
                    }

                    // Cek apakah belum waktunya absen masuk
                    $settingsData = settings::first();
                    $bufferMasuk = $settingsData->absen_masuk_buffer_menit ?? 30;
                    $waktu_shift = strtotime($ms->tanggal . ' ' . $ms->Shift->jam_masuk);
                    $waktu_sekarang = time();
                    $selisih_menit = ($waktu_shift - $waktu_sekarang) / 60;
                    if ($selisih_menit > $bufferMasuk) {
                        return response()->json('tooEarly');
                    }

                    // GPS Location validation - Safe null handling
                    $lokasi = $user->Lokasi;
                    $lat_kantor = null;
                    $long_kantor = null;
                    $radius = null;

                    if ($lokasi) {
                        $lat_kantor = $lokasi->lat_kantor;
                        $long_kantor = $lokasi->long_kantor;
                        $radius = $lokasi->radius;
                    }

                    // Only validate location if lock_location is enabled and lokasi exists
                    if ($ms->lock_location == 1) {
                        if (!$lokasi) {
                            return response()->json(['status' => 'error', 'message' => 'Lokasi kerja belum diatur. Hubungi admin.']);
                        }
                        if ($lat_kantor && $long_kantor && $radius && $request["lat"] && $request["long"]) {
                            $jarak_masuk = $this->distance($request["lat"], $request["long"], $lat_kantor, $long_kantor, "K") * 1000;
                            if ($jarak_masuk > $radius) {
                                return response()->json('outlocation');
                            }
                        }
                    }

                    $status_absen = "Masuk";
                    $jam_absen = date('H:i');
                    $tgl_skrg = date("Y-m-d");

                    $awal = strtotime($ms->tanggal . $ms->Shift->jam_masuk);
                    $akhir = strtotime($tgl_skrg . $jam_absen);
                    $diff = $akhir - $awal;

                    if ($diff <= 0) {
                        $telat = 0;
                        $jenis_kinerja = JenisKinerja::where('nama', 'Presensi Kehadiran Ontime')->first();
                        // Only create performance record if jenis_kinerja exists
                        if ($jenis_kinerja) {
                            $laporan_kinerja_before = LaporanKinerja::where('user_id', $user->id)->latest()->first();
                            $penilaian_berjalan = $laporan_kinerja_before
                                ? $laporan_kinerja_before->penilaian_berjalan + $jenis_kinerja->bobot
                                : $jenis_kinerja->bobot;
                            LaporanKinerja::create([
                                'user_id' => $user->id,
                                'tanggal' => $tgl_skrg,
                                'jenis_kinerja_id' => $jenis_kinerja->id,
                                'nilai' => $jenis_kinerja->bobot,
                                'penilaian_berjalan' => $penilaian_berjalan,
                                'reference' => 'App\Models\MappingShift',
                                'reference_id' => $ms->id,
                            ]);
                        }
                    } else {
                        $telat = $diff;
                        $jenis_kinerja = JenisKinerja::where('nama', 'Telat Presensi Masuk')->first();
                        // Only create performance record if jenis_kinerja exists
                        if ($jenis_kinerja) {
                            $laporan_kinerja_before = LaporanKinerja::where('user_id', $user->id)->latest()->first();
                            $penilaian_berjalan = $laporan_kinerja_before
                                ? $laporan_kinerja_before->penilaian_berjalan + $jenis_kinerja->bobot
                                : $jenis_kinerja->bobot;
                            LaporanKinerja::create([
                                'user_id' => $user->id,
                                'tanggal' => $tgl_skrg,
                                'jenis_kinerja_id' => $jenis_kinerja->id,
                                'nilai' => $jenis_kinerja->bobot,
                                'penilaian_berjalan' => $penilaian_berjalan,
                                'reference' => 'App\Models\MappingShift',
                                'reference_id' => $ms->id,
                            ]);
                        }
                    }

                    $ms->update([
                        'jam_absen' => $jam_absen,
                        'telat' => $telat,
                        'status_absen' => $status_absen
                    ]);
                    return response()->json('masuk');
                } else {
                    return response()->json('selesai');
                }
            } else {
                return response()->json('noMs');
            }
        } else {
            return response()->json('noUser');
        }
    }

    public function presensiStore(Request $request)
    {
        // Check IP restriction
        $ipCheck = AttendanceSecurityController::checkIpRestriction();
        if (!$ipCheck['allowed']) {
            return response()->json(['status' => 'ip_blocked', 'message' => $ipCheck['message']]);
        }

        date_default_timezone_set('Asia/Jakarta');
        $currentDate = date('Y-m-d');
        $user = User::where('username', $request['username'])->first();
        if ($user) {
            $ms = MappingShift::where('user_id', $user->id)->where('tanggal', $currentDate)->first();
            if ($ms) {
                if ($ms->jam_absen == null) {
                    // Check if Shift relation exists
                    if (!$ms->Shift) {
                        return response()->json(['success' => false, 'message' => 'Data shift tidak ditemukan. Hubungi admin.'], 400);
                    }

                    // Cek apakah belum waktunya absen masuk
                    $settingsData = settings::first();
                    $bufferMasuk = $settingsData->absen_masuk_buffer_menit ?? 30;
                    $waktu_shift = strtotime($ms->tanggal . ' ' . $ms->Shift->jam_masuk);
                    $waktu_sekarang = time();
                    $selisih_menit = ($waktu_shift - $waktu_sekarang) / 60;
                    if ($selisih_menit > $bufferMasuk) {
                        return response()->json('tooEarly');
                    }

                    // GPS Location validation - Safe null handling
                    $lokasi = $user->Lokasi;
                    $lat_kantor = null;
                    $long_kantor = null;
                    $radius = null;
                    $jarak_masuk = 0;

                    if ($lokasi) {
                        $lat_kantor = $lokasi->lat_kantor;
                        $long_kantor = $lokasi->long_kantor;
                        $radius = $lokasi->radius;
                    }

                    // Only validate location if lock_location is enabled
                    if ($ms->lock_location == 1) {
                        if (!$lokasi) {
                            return response()->json(['success' => false, 'message' => 'Lokasi kerja belum diatur. Hubungi admin.'], 400);
                        }
                        if ($lat_kantor && $long_kantor && $radius && $request["lat"] && $request["long"]) {
                            $jarak_masuk = $this->distance($request["lat"], $request["long"], $lat_kantor, $long_kantor, "K") * 1000;
                            if ($jarak_masuk > $radius) {
                                return response()->json('outlocation');
                            }
                        }
                    } else if ($lat_kantor && $long_kantor && $request["lat"] && $request["long"]) {
                        // Calculate distance even if lock is off (for logging purposes)
                        $jarak_masuk = $this->distance($request["lat"], $request["long"], $lat_kantor, $long_kantor, "K") * 1000;
                    }

                    // Continue with image processing
                    try {
                        $image = $request["image"];

                        // Validate image data exists and is not empty
                        if (empty($image)) {
                            return response()->json(['success' => false, 'message' => 'Foto absen tidak boleh kosong'], 400);
                        }

                        // Validate base64 format
                        if (!str_contains($image, ';base64,')) {
                            return response()->json(['success' => false, 'message' => 'Format foto tidak valid. Silakan ambil foto ulang.'], 400);
                        }

                        $image_parts = explode(";base64,", $image);

                        // Validate that we have both parts
                        if (!isset($image_parts[1]) || empty($image_parts[1])) {
                            return response()->json(['success' => false, 'message' => 'Data foto tidak lengkap. Silakan ambil foto ulang.'], 400);
                        }

                        $image_base64 = base64_decode($image_parts[1], true);

                        // Validate base64 decode success
                        if ($image_base64 === false) {
                            return response()->json(['success' => false, 'message' => 'Gagal memproses foto. Silakan coba lagi.'], 400);
                        }

                        $fileName = 'foto_jam_absen/' . uniqid() . '.png';
                    } catch (\Exception $e) {
                        \Log::error('Presensi Masuk Image Error: ' . $e->getMessage());
                        return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memproses foto. Silakan coba lagi.'], 500);
                    }

                    $status_absen = "Masuk";
                    $jam_absen = date('H:i');
                    $tgl_skrg = date("Y-m-d");

                    $awal = strtotime($ms->tanggal . $ms->Shift->jam_masuk);
                    $akhir = strtotime($tgl_skrg . $jam_absen);
                    $diff = $akhir - $awal;

                    if ($diff <= 0) {
                        $telat = 0;
                        $jenis_kinerja = JenisKinerja::where('nama', 'Presensi Kehadiran Ontime')->first();
                        // Only create performance record if jenis_kinerja exists
                        if ($jenis_kinerja) {
                            $laporan_kinerja_before = LaporanKinerja::where('user_id', $user->id)->latest()->first();
                            $penilaian_berjalan = $laporan_kinerja_before
                                ? $laporan_kinerja_before->penilaian_berjalan + $jenis_kinerja->bobot
                                : $jenis_kinerja->bobot;
                            LaporanKinerja::create([
                                'user_id' => $user->id,
                                'tanggal' => $tgl_skrg,
                                'jenis_kinerja_id' => $jenis_kinerja->id,
                                'nilai' => $jenis_kinerja->bobot,
                                'penilaian_berjalan' => $penilaian_berjalan,
                                'reference' => 'App\Models\MappingShift',
                                'reference_id' => $ms->id,
                            ]);
                        }
                    } else {
                        $telat = $diff;
                        $jenis_kinerja = JenisKinerja::where('nama', 'Telat Presensi Masuk')->first();
                        // Only create performance record if jenis_kinerja exists
                        if ($jenis_kinerja) {
                            $laporan_kinerja_before = LaporanKinerja::where('user_id', $user->id)->latest()->first();
                            $penilaian_berjalan = $laporan_kinerja_before
                                ? $laporan_kinerja_before->penilaian_berjalan + $jenis_kinerja->bobot
                                : $jenis_kinerja->bobot;
                            LaporanKinerja::create([
                                'user_id' => $user->id,
                                'tanggal' => $tgl_skrg,
                                'jenis_kinerja_id' => $jenis_kinerja->id,
                                'nilai' => $jenis_kinerja->bobot,
                                'penilaian_berjalan' => $penilaian_berjalan,
                                'reference' => 'App\Models\MappingShift',
                                'reference_id' => $ms->id,
                            ]);
                        }
                    }

                    Storage::disk('public')->put($fileName, $image_base64);
                    $ms->update([
                        'jam_absen' => $jam_absen,
                        'telat' => $telat,
                        'foto_jam_absen' => $fileName,
                        'lat_absen' => $request["lat"],
                        'long_absen' => $request["long"],
                        'jarak_masuk' => $jarak_masuk,
                        'status_absen' => $status_absen
                    ]);
                    return response()->json('masuk');
                } else {
                    return response()->json('selesai');
                }
            } else {
                return response()->json('noMs');
            }
        } else {
            return response()->json('noUser');
        }
    }

    public function presensiPulangStore(Request $request)
    {
        // Check IP restriction
        $ipCheck = AttendanceSecurityController::checkIpRestriction();
        if (!$ipCheck['allowed']) {
            return response()->json(['status' => 'ip_blocked', 'message' => $ipCheck['message']]);
        }

        date_default_timezone_set('Asia/Jakarta');
        $currentDate = date('Y-m-d');
        $user = User::where('username', $request['username'])->first();
        if ($user) {
            $ms = MappingShift::where('user_id', $user->id)->where('tanggal', $currentDate)->first();
            if ($ms) {
                // Cek apakah sudah absen masuk
                if ($ms->jam_absen == null) {
                    return response()->json('notClockedIn');
                }

                if ($ms->jam_pulang == null) {
                    // Check if Shift relation exists
                    if (!$ms->Shift) {
                        return response()->json(['success' => false, 'message' => 'Data shift tidak ditemukan. Hubungi admin.'], 400);
                    }

                    // Cek apakah belum waktunya pulang (max 30 menit sebelum jadwal)
                    $shiftmasuk = $ms->Shift->jam_masuk;
                    $shiftpulang = $ms->Shift->jam_keluar;
                    $tanggal = $ms->tanggal;

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

                    $settingsData = settings::first();
                    $bufferPulang = $settingsData->absen_pulang_buffer_menit ?? 30;
                    if ($selisih_menit > $bufferPulang) {
                        return response()->json('tooEarlyPulang');
                    }

                    // GPS Location validation - Safe null handling
                    $lokasi = $user->Lokasi;
                    $lat_kantor = null;
                    $long_kantor = null;
                    $radius = null;
                    $jarak_pulang = 0;

                    if ($lokasi) {
                        $lat_kantor = $lokasi->lat_kantor;
                        $long_kantor = $lokasi->long_kantor;
                        $radius = $lokasi->radius;
                    }

                    // Only validate location if lock_location is enabled
                    if ($ms->lock_location == 1) {
                        if (!$lokasi) {
                            return response()->json(['success' => false, 'message' => 'Lokasi kerja belum diatur. Hubungi admin.'], 400);
                        }
                        if ($lat_kantor && $long_kantor && $radius && $request["lat"] && $request["long"]) {
                            $jarak_pulang = $this->distance($request["lat"], $request["long"], $lat_kantor, $long_kantor, "K") * 1000;
                            if ($jarak_pulang > $radius) {
                                return response()->json('outlocation');
                            }
                        }
                    } else if ($lat_kantor && $long_kantor && $request["lat"] && $request["long"]) {
                        // Calculate distance even if lock is off (for logging purposes)
                        $jarak_pulang = $this->distance($request["lat"], $request["long"], $lat_kantor, $long_kantor, "K") * 1000;
                    }

                    // Continue with image processing
                    try {
                        $image = $request["image"];

                        // Validate image data exists and is not empty
                        if (empty($image)) {
                            return response()->json(['success' => false, 'message' => 'Foto absen tidak boleh kosong'], 400);
                        }

                        // Validate base64 format
                        if (!str_contains($image, ';base64,')) {
                            return response()->json(['success' => false, 'message' => 'Format foto tidak valid. Silakan ambil foto ulang.'], 400);
                        }

                        $image_parts = explode(";base64,", $image);

                        // Validate that we have both parts
                        if (!isset($image_parts[1]) || empty($image_parts[1])) {
                            return response()->json(['success' => false, 'message' => 'Data foto tidak lengkap. Silakan ambil foto ulang.'], 400);
                        }

                        $image_base64 = base64_decode($image_parts[1], true);

                        // Validate base64 decode success
                        if ($image_base64 === false) {
                            return response()->json(['success' => false, 'message' => 'Gagal memproses foto. Silakan coba lagi.'], 400);
                        }

                        $fileName = 'foto_jam_pulang/' . uniqid() . '.png';
                    } catch (\Exception $e) {
                        \Log::error('Presensi Pulang Image Error: ' . $e->getMessage());
                        return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memproses foto. Silakan coba lagi.'], 500);
                    }

                    $jam_pulang = date('H:i');

                    $new_tanggal = "";
                    $timeMasuk = strtotime($ms->Shift->jam_masuk);
                    $timePulang = strtotime($ms->Shift->jam_keluar);

                    if ($timePulang < $timeMasuk) {
                        $new_tanggal = date('Y-m-d', strtotime('+1 days', strtotime($ms->tanggal)));
                    } else {
                        $new_tanggal = $ms->tanggal;
                    }

                    $tgl_skrg = date("Y-m-d");

                    $akhir = strtotime($new_tanggal . $ms->Shift->jam_keluar);
                    $awal = strtotime($tgl_skrg . $jam_pulang);
                    $diff = $akhir - $awal;

                    if ($diff <= 0) {
                        $pulang_cepat = 0;
                        $jenis_kinerja = JenisKinerja::where('nama', 'Pulang tepat waktu')->first();
                        // Only create performance record if jenis_kinerja exists
                        if ($jenis_kinerja) {
                            $laporan_kinerja_before = LaporanKinerja::where('user_id', $user->id)->latest()->first();
                            $penilaian_berjalan = $laporan_kinerja_before
                                ? $laporan_kinerja_before->penilaian_berjalan + $jenis_kinerja->bobot
                                : $jenis_kinerja->bobot;
                            LaporanKinerja::create([
                                'user_id' => $user->id,
                                'tanggal' => $tgl_skrg,
                                'jenis_kinerja_id' => $jenis_kinerja->id,
                                'nilai' => $jenis_kinerja->bobot,
                                'penilaian_berjalan' => $penilaian_berjalan,
                                'reference' => 'App\Models\MappingShift',
                                'reference_id' => $ms->id,
                            ]);
                        }
                    } else {
                        $pulang_cepat = $diff;
                        $jenis_kinerja = JenisKinerja::where('nama', 'Pulang Sebelum waktunya')->first();
                        // Only create performance record if jenis_kinerja exists
                        if ($jenis_kinerja) {
                            $laporan_kinerja_before = LaporanKinerja::where('user_id', $user->id)->latest()->first();
                            $penilaian_berjalan = $laporan_kinerja_before
                                ? $laporan_kinerja_before->penilaian_berjalan + $jenis_kinerja->bobot
                                : $jenis_kinerja->bobot;
                            LaporanKinerja::create([
                                'user_id' => $user->id,
                                'tanggal' => $tgl_skrg,
                                'jenis_kinerja_id' => $jenis_kinerja->id,
                                'nilai' => $jenis_kinerja->bobot,
                                'penilaian_berjalan' => $penilaian_berjalan,
                                'reference' => 'App\Models\MappingShift',
                                'reference_id' => $ms->id,
                            ]);
                        }
                    }

                    Storage::disk('public')->put($fileName, $image_base64);
                    $ms->update([
                        'jam_pulang' => $jam_pulang,
                        'pulang_cepat' => $pulang_cepat,
                        'foto_jam_pulang' => $fileName,
                        'lat_pulang' => $request["lat"],
                        'long_pulang' => $request["long"],
                        'jarak_pulang' => $jarak_pulang,
                    ]);
                    return response()->json('pulang');
                } else {
                    return response()->json('selesai');
                }
            } else {
                return response()->json('noMs');
            }
        } else {
            return response()->json('noUser');
        }
    }

    public function qrPulangStore(Request $request)
    {
        // Check IP restriction
        $ipCheck = AttendanceSecurityController::checkIpRestriction();
        if (!$ipCheck['allowed']) {
            return response()->json(['status' => 'ip_blocked', 'message' => $ipCheck['message']]);
        }

        date_default_timezone_set('Asia/Jakarta');
        $currentDate = date('Y-m-d');
        $user = User::where('username', $request['username'])->first();
        if ($user) {
            $ms = MappingShift::where('user_id', $user->id)->where('tanggal', $currentDate)->first();
            if ($ms) {
                // Cek apakah sudah absen masuk
                if ($ms->jam_absen == null) {
                    return response()->json('notClockedIn');
                }

                if ($ms->jam_pulang == null) {
                    // Check if Shift relation exists
                    if (!$ms->Shift) {
                        return response()->json(['status' => 'error', 'message' => 'Data shift tidak ditemukan. Hubungi admin.']);
                    }

                    // Cek apakah belum waktunya pulang (max 30 menit sebelum jadwal)
                    $shiftmasuk = $ms->Shift->jam_masuk;
                    $shiftpulang = $ms->Shift->jam_keluar;
                    $tanggal = $ms->tanggal;

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

                    $settingsData = settings::first();
                    $bufferPulang = $settingsData->absen_pulang_buffer_menit ?? 30;
                    if ($selisih_menit > $bufferPulang) {
                        return response()->json('tooEarlyPulang');
                    }

                    // GPS Location validation - Safe null handling
                    $lokasi = $user->Lokasi;
                    $lat_kantor = null;
                    $long_kantor = null;
                    $radius = null;

                    if ($lokasi) {
                        $lat_kantor = $lokasi->lat_kantor;
                        $long_kantor = $lokasi->long_kantor;
                        $radius = $lokasi->radius;
                    }

                    // Only validate location if lock_location is enabled
                    if ($ms->lock_location == 1) {
                        if (!$lokasi) {
                            return response()->json(['status' => 'error', 'message' => 'Lokasi kerja belum diatur. Hubungi admin.']);
                        }
                        if ($lat_kantor && $long_kantor && $radius && $request["lat"] && $request["long"]) {
                            $jarak_pulang = $this->distance($request["lat"], $request["long"], $lat_kantor, $long_kantor, "K") * 1000;
                            if ($jarak_pulang > $radius) {
                                return response()->json('outlocation');
                            }
                        }
                    }

                    $jam_pulang = date('H:i');
                    $timeMasuk = strtotime($ms->Shift->jam_masuk);
                    $timePulang = strtotime($ms->Shift->jam_keluar);

                    if ($timePulang < $timeMasuk) {
                        $new_tanggal = date('Y-m-d', strtotime('+1 days', strtotime($ms->tanggal)));
                    } else {
                        $new_tanggal = $ms->tanggal;
                    }

                    $tgl_skrg = date("Y-m-d");

                    $akhir = strtotime($new_tanggal . $ms->Shift->jam_keluar);
                    $awal = strtotime($tgl_skrg . $jam_pulang);
                    $diff = $akhir - $awal;

                    if ($diff <= 0) {
                        $pulang_cepat = 0;
                        $jenis_kinerja = JenisKinerja::where('nama', 'Pulang tepat waktu')->first();
                        // Only create performance record if jenis_kinerja exists
                        if ($jenis_kinerja) {
                            $laporan_kinerja_before = LaporanKinerja::where('user_id', $user->id)->latest()->first();
                            $penilaian_berjalan = $laporan_kinerja_before
                                ? $laporan_kinerja_before->penilaian_berjalan + $jenis_kinerja->bobot
                                : $jenis_kinerja->bobot;
                            LaporanKinerja::create([
                                'user_id' => $user->id,
                                'tanggal' => $tgl_skrg,
                                'jenis_kinerja_id' => $jenis_kinerja->id,
                                'nilai' => $jenis_kinerja->bobot,
                                'penilaian_berjalan' => $penilaian_berjalan,
                                'reference' => 'App\Models\MappingShift',
                                'reference_id' => $ms->id,
                            ]);
                        }
                    } else {
                        $pulang_cepat = $diff;
                        $jenis_kinerja = JenisKinerja::where('nama', 'Pulang Sebelum waktunya')->first();
                        // Only create performance record if jenis_kinerja exists
                        if ($jenis_kinerja) {
                            $laporan_kinerja_before = LaporanKinerja::where('user_id', $user->id)->latest()->first();
                            $penilaian_berjalan = $laporan_kinerja_before
                                ? $laporan_kinerja_before->penilaian_berjalan + $jenis_kinerja->bobot
                                : $jenis_kinerja->bobot;
                            LaporanKinerja::create([
                                'user_id' => $user->id,
                                'tanggal' => $tgl_skrg,
                                'jenis_kinerja_id' => $jenis_kinerja->id,
                                'nilai' => $jenis_kinerja->bobot,
                                'penilaian_berjalan' => $penilaian_berjalan,
                                'reference' => 'App\Models\MappingShift',
                                'reference_id' => $ms->id,
                            ]);
                        }
                    }

                    $ms->update([
                        'jam_pulang' => $jam_pulang,
                        'pulang_cepat' => $pulang_cepat,
                    ]);
                    return response()->json('pulang');
                } else {
                    return response()->json('selesai');
                }
            } else {
                return response()->json('noMs');
            }
        } else {
            return response()->json('noUser');
        }
    }

    public function distance($lat1, $lon1, $lat2, $lon2, $unit)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
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

    public function switch(Request $request, $id)
    {
        // Disabled: Admin should not switch to user mode for security
        // Only allow if current user already has switch session (switching back)
        if (!$request->session()->has('user_is_switched')) {
            Alert::error('Tidak Diizinkan', 'Fitur switch ke user telah dinonaktifkan');
            return back();
        }

        $request->session()->put('existing_user_id', Auth::user()->id);
        $request->session()->put('user_is_switched', true);
        Auth::loginUsingId($id);
        return redirect()->to('/');
    }

    public function ajaxGetNeural()
    {
        $inp = file_get_contents('neural.json');
        $tempArray = json_decode($inp);
        $jsonData = json_encode($tempArray);
        echo $jsonData;
    }

    public function registerProses(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'username' => 'required|unique:users|min:3|max:50',
            'email' => 'required|email:dns|unique:users',
            'password' => 'required|confirmed|min:6|max:255',
            'jabatan_id' => 'required',
            'lokasi_id' => 'required',
        ]);

        if ($request->file('foto_karyawan')) {
            $validatedData['foto_karyawan'] = $request->file('foto_karyawan')->store('foto_karyawan', 'public');
        }

        $validatedData['is_admin'] = 'user';
        $validatedData['password'] = Hash::make($validatedData['password']);
        User::create($validatedData);
        return redirect('/')->with('success', 'Berhasil Register! Silahkan Login');
    }

    public function loginProses(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');

        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('username', $request->username)->first();

        if ($user) {
            if ($user->masa_berlaku && $user->masa_berlaku <= date('Y-m-d')) {
                Alert::error('Failed', 'Username / Password Salah / Akun Tidak Aktif');
                return back();
            } else {
                if (Auth::attempt($credentials)) {
                    // FIX: Preserve existing is_admin value
                    // Only sync from Spatie role if user has admin role but is_admin is not set
                    // Do NOT reset admin to user - this was causing the bug
                    if ($user->hasRole('admin') && $user->is_admin !== 'admin') {
                        $user->update([
                            'is_admin' => 'admin'
                        ]);
                    }
                    // Note: Removed the else block that was resetting is_admin to 'user'
                    // The is_admin column should already be set correctly from user creation/registration

                    $request->session()->regenerate();
                    return redirect()->intended('/dashboard');
                } else {
                    Alert::error('Failed', 'Username / Password Salah / Akun Tidak Aktif');
                    return back();
                }
            }
        } else {
            Alert::error('Failed', 'Username / Password Salah / Akun Tidak Aktif');
            return back();
        }

    }


    public function loginProsesUser(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');

        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('username', $request->username)->first();

        if ($user) {
            if ($user->masa_berlaku && $user->masa_berlaku <= date('Y-m-d')) {
                Alert::error('Failed', 'Username / Password Salah / Akun Tidak Aktif');
                return back();
            } else {
                if (Auth::attempt($credentials)) {
                    $request->session()->regenerate();
                    return redirect()->intended('/dashboard');
                } else {
                    Alert::error('Failed', 'Username / Password Salah / Akun Tidak Aktif');
                    return back();
                }
            }
        } else {
            Alert::error('Failed', 'Username / Password Salah / Akun Tidak Aktif');
            return back();
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function forgotPassword()
    {
        $title = 'Forgot Password';
        return view('auth.forgot-password', compact(
            'title'
        ));
    }

    public function forgotPasswordLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['success' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }


    public function showResetForm($token)
    {
        $title = 'Reset Password';
        return view('auth.passwords.reset', compact(
            'token',
            'title'
        ));
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
