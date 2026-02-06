<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Lembur;
use App\Models\Lokasi;
use App\Models\settings;
use Illuminate\Http\Request;
use App\Events\NotifApproval;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class LemburController extends Controller
{
    public function index()
    {
        date_default_timezone_set('Asia/Jakarta');
        $user_login = auth()->user()->id;
        $tanggal = "";
        $tglskrg = date("Y-m-d");
        $tglkmrn = date('Y-m-d', strtotime('-1 days'));
        $lembur = Lembur::where('user_id', $user_login)->where('tanggal', $tglkmrn)->get();
        if ($lembur->count() > 0) {
            foreach ($lembur as $l) {
                $jam_keluar = $l->jam_keluar;
            }
        } else {
            $jam_keluar = "-";
        }
        if ($jam_keluar == null) {
            $tanggal = $tglkmrn;
        } else {
            $tanggal = $tglskrg;
        }

        if (auth()->user()->is_admin == 'admin') {
            return view('lembur.index', [
                'title' => 'Absen Lembur',
                'lembur' => Lembur::where('user_id', $user_login)->where('tanggal', $tanggal)->get()
            ]);
        } else {
            return view('lembur.indexuser', [
                'title' => 'Absen Lembur',
                'lembur' => Lembur::where('user_id', $user_login)->where('tanggal', $tanggal)->first()
            ]);
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

    public function masuk(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');

        try {
            // GPS Location validation - Safe null handling
            $lokasi = auth()->user()->Lokasi;
            if (!$lokasi) {
                Alert::error('Error', 'Lokasi kerja belum diatur. Hubungi admin.');
                return redirect('/lembur');
            }

            $lat_kantor = $lokasi->lat_kantor;
            $long_kantor = $lokasi->long_kantor;
            $radius = $lokasi->radius;
            $nama_lokasi = $lokasi->nama_lokasi ?? 'Kantor';

            // Calculate distance if location data available
            if ($lat_kantor && $long_kantor && $request["lat_masuk"] && $request["long_masuk"]) {
                $request["jarak_masuk"] = $this->distance($request["lat_masuk"], $request["long_masuk"], $lat_kantor, $long_kantor, "K") * 1000;
            } else {
                $request["jarak_masuk"] = 0;
            }

            if ($request["jarak_masuk"] > $radius && $radius > 0) {
                Alert::error('Diluar Jangkauan', 'Lokasi Anda Diluar Radius ' . $nama_lokasi);
                return redirect('/lembur');
            }

            $foto_jam_masuk = $request["foto_jam_masuk"];

            // Validate image
            if (empty($foto_jam_masuk) || !str_contains($foto_jam_masuk, ';base64,')) {
                Alert::error('Error', 'Foto tidak valid. Silakan ambil foto ulang.');
                return redirect('/lembur');
            }

            $image_parts = explode(";base64,", $foto_jam_masuk);
            if (!isset($image_parts[1]) || empty($image_parts[1])) {
                Alert::error('Error', 'Data foto tidak lengkap. Silakan coba lagi.');
                return redirect('/lembur');
            }

            $image_base64 = base64_decode($image_parts[1], true);
            if ($image_base64 === false) {
                Alert::error('Error', 'Gagal memproses foto. Silakan coba lagi.');
                return redirect('/lembur');
            }

            $fileName = 'foto_jam_masuk_lembur/' . uniqid() . '.png';

            Storage::disk('public')->put($fileName, $image_base64);

            $request["foto_jam_masuk"] = $fileName;

            $validatedData = $request->validate([
                'user_id' => 'required',
                'tanggal' => 'required',
                'jam_masuk' => 'required',
                'foto_jam_masuk' => 'required',
                'lat_masuk' => 'required',
                'long_masuk' => 'required',
                'jarak_masuk' => 'required',
                'status' => 'required'
            ]);

            $validatedData['lokasi_id'] = auth()->user()->lokasi_id;

            Lembur::create($validatedData);

            $request->session()->flash('success', 'Berhasil Masuk Lembur');

            return redirect('/lembur');
        } catch (\Exception $e) {
            \Log::error('Lembur Masuk Error: ' . $e->getMessage());
            Alert::error('Error', 'Terjadi kesalahan saat absen lembur. Silakan coba lagi.');
            return redirect('/lembur');
        }
    }

    public function pulang(Request $request, $id)
    {
        date_default_timezone_set('Asia/Jakarta');

        try {
            // GPS Location validation - Safe null handling
            $lokasi = auth()->user()->Lokasi;
            if (!$lokasi) {
                Alert::error('Error', 'Lokasi kerja belum diatur. Hubungi admin.');
                return redirect('/lembur');
            }

            $lat_kantor = $lokasi->lat_kantor;
            $long_kantor = $lokasi->long_kantor;
            $radius = $lokasi->radius;
            $nama_lokasi = $lokasi->nama_lokasi ?? 'Kantor';

            // Calculate distance if location data available
            if ($lat_kantor && $long_kantor && $request["lat_keluar"] && $request["long_keluar"]) {
                $request["jarak_keluar"] = $this->distance($request["lat_keluar"], $request["long_keluar"], $lat_kantor, $long_kantor, "K") * 1000;
            } else {
                $request["jarak_keluar"] = 0;
            }

            if ($request["jarak_keluar"] > $radius && $radius > 0) {
                Alert::error('Diluar Jangkauan', 'Lokasi Anda Diluar Radius ' . $nama_lokasi);
                return redirect('/lembur');
            }

            $foto_jam_keluar = $request["foto_jam_keluar"];

            // Validate image
            if (empty($foto_jam_keluar) || !str_contains($foto_jam_keluar, ';base64,')) {
                Alert::error('Error', 'Foto tidak valid. Silakan ambil foto ulang.');
                return redirect('/lembur');
            }

            $image_parts = explode(";base64,", $foto_jam_keluar);
            if (!isset($image_parts[1]) || empty($image_parts[1])) {
                Alert::error('Error', 'Data foto tidak lengkap. Silakan coba lagi.');
                return redirect('/lembur');
            }

            $image_base64 = base64_decode($image_parts[1], true);
            if ($image_base64 === false) {
                Alert::error('Error', 'Gagal memproses foto. Silakan coba lagi.');
                return redirect('/lembur');
            }

            $fileName = 'foto_jam_keluar_lembur/' . uniqid() . '.png';

            Storage::disk('public')->put($fileName, $image_base64);

            $request["foto_jam_keluar"] = $fileName;

            $lembur = Lembur::find($id);
            if (!$lembur) {
                Alert::error('Error', 'Data lembur tidak ditemukan.');
                return redirect('/lembur');
            }

            $jam_masuk = $lembur->jam_masuk;
            $time_masuk = strtotime($jam_masuk);
            $time_keluar = strtotime($request["jam_keluar"]);

            $diff = $time_keluar - $time_masuk;

            $request["total_lembur"] = $diff;

            $validatedData = $request->validate([
                'jam_keluar' => 'required',
                'lat_keluar' => 'required',
                'long_keluar' => 'required',
                'jarak_keluar' => 'required',
                'foto_jam_keluar' => 'required',
                'total_lembur' => 'required'
            ]);

            $lembur->update($validatedData);

            $user_roles = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin')
                    ->orWhere('name', 'hrd')
                    ->orWhere('name', 'general_manager');
            });

            $kepala_cabang = User::whereHas('roles', function ($query) {
                $query->where('name', 'kepala_cabang');
            })->where('lokasi_id', auth()->user()->lokasi_id);

            $users = $user_roles->union($kepala_cabang)->get();

            foreach ($users as $user) {
                $type = 'Approval';
                $notif = 'Pengajuan Lembur Dari ' . auth()->user()->name . ' Butuh Approval Anda';
                $url = url('/data-lembur?user_id=' . $lembur->user_id . '&mulai=' . $lembur->tanggal . '&akhir=' . $lembur->tanggal);

                $user->messages = [
                    'user_id' => auth()->user()->id,
                    'from' => auth()->user()->name,
                    'message' => $notif,
                    'action' => '/data-lembur?user_id=' . $user->id . '&mulai=' . $lembur->tanggal . '&akhir=' . $lembur->tanggal
                ];
                $user->notify(new \App\Notifications\UserNotification);

                NotifApproval::dispatch($type, $user->id, $notif, $url);

                WhatsAppService::send($user->telepon, $notif . "\n" . $url);
            }

            return redirect('/lembur')->with('success', 'Berhasil Pulang Lembur');
        } catch (\Exception $e) {
            \Log::error('Lembur Pulang Error: ' . $e->getMessage());
            Alert::error('Error', 'Terjadi kesalahan saat absen lembur. Silakan coba lagi.');
            return redirect('/lembur');
        }
    }

    public function dataLembur(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');

        $user = User::find(auth()->user()->id);
        $user->update([
            'is_admin' => 'admin'
        ]);

        $tglskrg = date('Y-m-d');

        $user_id = request()->input('user_id');
        $mulai = request()->input('mulai');
        $akhir = request()->input('akhir');

        $data_lembur = Lembur::when(auth()->user()->hasRole('kepala_cabang'), function ($query) {
            return $query->where('lokasi_id', auth()->user()->lokasi_id);
        })
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('user_id', $user_id);
            })
            ->when(!$mulai && !$akhir, function ($query) use ($tglskrg) {
                return $query->where('tanggal', $tglskrg);
            })
            ->when($mulai && $akhir, function ($query) use ($mulai, $akhir) {
                return $query->whereBetween('tanggal', [$mulai, $akhir]);
            })
            ->orderBy('tanggal', 'ASC')
            ->orderBy('id', 'DESC');

        return view('lembur.datalembur', [
            'title' => 'Data Lembur',
            'user' => User::select('id', 'name')->get(),
            'data_lembur' => $data_lembur->paginate(10)->withQueryString()
        ]);
    }

    public function myLembur(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $tglskrg = date('Y-m-d');

        $user_id = request()->input('user_id');
        $mulai = request()->input('mulai');
        $akhir = request()->input('akhir');

        $data_lembur = Lembur::when(auth()->user()->hasRole('kepala_cabang'), function ($query) {
            return $query->where('lokasi_id', auth()->user()->lokasi_id);
        })
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('user_id', $user_id);
            })
            ->when(!$mulai && !$akhir, function ($query) use ($tglskrg) {
                return $query->where('tanggal', $tglskrg);
            })
            ->when($mulai && $akhir, function ($query) use ($mulai, $akhir) {
                return $query->whereBetween('tanggal', [$mulai, $akhir]);
            })
            ->when(auth()->user()->is_admin == 'user', function ($query) {
                return $query->where('user_id', auth()->user()->id);
            })
            ->orderBy('tanggal', 'ASC')
            ->orderBy('id', 'DESC');

        return view('lembur.mylemburuser', [
            'title' => 'My Lembur',
            'data_lembur' => $data_lembur->paginate(10)->withQueryString()
        ]);
    }

    public function approval(Request $request, $id)
    {
        $lembur = Lembur::find($id);
        $validated = $request->validate([
            'status' => 'required',
            'notes' => 'nullable',
            'approved_by' => 'required',
        ]);

        if ($request['status'] == 'Approved') {
            $stat = 'Approve';
            $user = User::find($lembur->user_id);
            $type = 'Approved';
            $notif = 'Lembur Anda Telah Di Approve Oleh ' . auth()->user()->name;
            $url = url('/my-lembur?mulai=' . $lembur->tanggal . '&akhir=' . $lembur->tanggal);

            $user->messages = [
                'user_id' => auth()->user()->id,
                'from' => auth()->user()->name,
                'message' => $notif,
                'action' => '/my-lembur?mulai=' . $lembur->tanggal . '&akhir=' . $lembur->tanggal
            ];
            $user->notify(new \App\Notifications\UserNotification);

            NotifApproval::dispatch($type, $user->id, $notif, $url);

            WhatsAppService::send($user->telepon, $notif . "\n" . $url);
        } else {
            $stat = 'Reject';
            $user = User::find($lembur->user_id);
            $type = 'Rejected';
            $notif = 'Lembur Anda Telah Di Reject Oleh ' . auth()->user()->name;
            $url = url('/my-lembur?mulai=' . $lembur->tanggal . '&akhir=' . $lembur->tanggal);

            $user->messages = [
                'user_id' => auth()->user()->id,
                'from' => auth()->user()->name,
                'message' => $notif,
                'action' => '/my-lembur?mulai=' . $lembur->tanggal . '&akhir=' . $lembur->tanggal
            ];
            $user->notify(new \App\Notifications\UserNotification);

            NotifApproval::dispatch($type, $user->id, $notif, $url);

            WhatsAppService::send($user->telepon, $notif . "\n" . $url);
        }

        $lembur->update($validated);
        return redirect('/data-lembur')->with('success', 'Berhasil ' . $stat . ' Lembur');
    }
}
