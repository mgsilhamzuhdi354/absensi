<?php

namespace App\Http\Controllers;

use App\Models\Kasbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Events\NotifApproval;
use App\Services\WhatsAppService;

class KasbonController extends Controller
{
    public function index()
    {
        $tanggal = request()->input('tanggal');
        $status = request()->input('status');
        if (auth()->user()->is_admin == 'admin') {
            $data = Kasbon::when($tanggal, function ($query) use ($tanggal) {
                                return $query->where('tanggal', $tanggal);
                            })
                            ->when($status, function ($query) use ($status) {
                                return $query->where('status', $status);
                            })
                            ->orderBy('id', 'DESC');
                            
            return view('kasbon.index', [
                'title' => 'Data Kasbon Pegawai',
                'data' => $data->paginate(10)->withQueryString()
            ]);
        } else {
           $data = Kasbon::where('user_id', auth()->user()->id)
                            ->when($tanggal, function ($query) use ($tanggal) {
                                return $query->where('tanggal', $tanggal);
                            })
                            ->when($status, function ($query) use ($status) {
                                return $query->where('status', $status);
                            })
                            ->orderBy('id', 'DESC');
            
            return view('kasbon.indexuser', [
                'title' => 'Data Kasbon Pegawai',
                'data' => $data->paginate(10)->withQueryString()
            ]);
        }
        

    }

    public function tambah()
    {
        if (auth()->user()->is_admin == 'admin') {
            return view('kasbon.tambah', [
                'title' => 'Tambah Data Kasbon',
                'data_user' => User::orderBy('name', 'asc')->get()
            ]);
        } else {
            return view('kasbon.tambahuser', [
                'title' => 'Tambah Data Kasbon',
                'data_user' => User::orderBy('name', 'asc')->get()
            ]);
        }

    }
    
    public function tambahProses(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required',
            'tanggal' => 'required',
            'nominal' => 'required',
            'keperluan' => 'required',
            'status' => 'required',
        ]);

        $validated['nominal'] = str_replace(',', '', $validated['nominal']);

        $kasbon = Kasbon::create($validated);
        
        // Kirim notifikasi ke admin/HRD/GM jika user mengajukan kasbon
        if (strtolower($validated['status']) == 'pending') {
            $user_pengaju = User::find($validated['user_id']);
            $nominal_formatted = number_format($kasbon->nominal, 0, ',', '.');
            
            $admin_users = User::where('is_admin', 'admin')->get();

            foreach ($admin_users as $user) {
                $type = 'Approval';
                $notif = 'Pengajuan Kasbon Rp ' . $nominal_formatted . ' dari ' . $user_pengaju->name . ' menunggu persetujuan Anda';
                $url = url('/kasbon');

                // Simpan notifikasi ke database
                try {
                    $user->messages = [
                        'user_id'   =>  $user_pengaju->id,
                        'from'      =>  $user_pengaju->name,
                        'message'   =>  $notif,
                        'action'    =>  '/kasbon'
                    ];
                    $user->notify(new \App\Notifications\UserNotification);
                } catch (\Exception $e) {
                    \Log::error('Notification error: ' . $e->getMessage());
                }

                // Dispatch event (optional, won't break if fails)
                try {
                    NotifApproval::dispatch($type, $user->id, $notif, $url);
                } catch (\Exception $e) {
                    \Log::error('NotifApproval event error: ' . $e->getMessage());
                }

                // WhatsApp (optional, won't break if fails)
                try {
                    WhatsAppService::send($user->telepon, $notif . "\n" . $url);
                } catch (\Exception $e) {
                    \Log::error('WhatsApp error: ' . $e->getMessage());
                }
            }
        }
        
        return redirect('/kasbon')->with('success', 'Data Berhasil Ditambahkan');
    }

    public function edit($id)
    {
        if (auth()->user()->is_admin == 'admin') {
            return view('kasbon.edit', [
                'title' => 'Edit Data Kasbon',
                'data_user' => User::orderBy('name', 'asc')->get(),
                'kasbon' => Kasbon::find($id),
            ]);
        } else {
            return view('kasbon.edituser', [
                'title' => 'Edit Data Kasbon',
                'data_user' => User::orderBy('name', 'asc')->get(),
                'kasbon' => Kasbon::find($id),
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $kasbon = Kasbon::find($id);
        $old_status = $kasbon->status;
        
        $validated = $request->validate([
            'user_id' => 'required',
            'tanggal' => 'required',
            'nominal' => 'required',
            'keperluan' => 'required',
            'status' => 'required',
        ]);

        $validated['nominal'] = str_replace(',', '', $validated['nominal']);

        // Hanya tambah saldo jika status berubah dari non-ACC menjadi ACC
        if ($validated['status'] == 'ACC' && $old_status != 'ACC') {
            $user = User::find($validated['user_id']);
            $user->update(['saldo_kasbon' => $user->saldo_kasbon + $validated['nominal']]);
        }
        
        // Kurangi saldo jika status berubah dari ACC menjadi non-ACC (pembatalan)
        if ($old_status == 'ACC' && $validated['status'] != 'ACC') {
            $user = User::find($validated['user_id']);
            $user->update(['saldo_kasbon' => $user->saldo_kasbon - $validated['nominal']]);
        }

        $kasbon->update($validated);
        
        // Kirim notifikasi ke user saat status berubah dari Pending
        if (strtolower($old_status) == 'pending' && strtolower($validated['status']) != 'pending') {
            $user_kasbon = User::find($kasbon->user_id);
            $nominal_formatted = number_format($kasbon->nominal, 0, ',', '.');
            
            if ($validated['status'] == 'ACC' || $validated['status'] == 'Acc') {
                $type = 'Approved';
                $notif = 'Pengajuan Kasbon Rp ' . $nominal_formatted . ' Anda telah DISETUJUI oleh ' . auth()->user()->name;
            } else {
                $type = 'Rejected';
                $notif = 'Pengajuan Kasbon Rp ' . $nominal_formatted . ' Anda telah DITOLAK oleh ' . auth()->user()->name;
            }
            
            $url = url('/kasbon');

            // Simpan notifikasi ke database
            try {
                $user_kasbon->messages = [
                    'user_id'   =>  auth()->user()->id,
                    'from'      =>  auth()->user()->name,
                    'message'   =>  $notif,
                    'action'    =>  '/kasbon'
                ];
                $user_kasbon->notify(new \App\Notifications\UserNotification);
            } catch (\Exception $e) {
                \Log::error('Kasbon notification error: ' . $e->getMessage());
            }

            try {
                NotifApproval::dispatch($type, $user_kasbon->id, $notif, $url);
            } catch (\Exception $e) {
                \Log::error('NotifApproval error: ' . $e->getMessage());
            }

            try {
                WhatsAppService::send($user_kasbon->telepon, $notif . "\n" . $url);
            } catch (\Exception $e) {
                \Log::error('WhatsApp error: ' . $e->getMessage());
            }
        }
        
        return redirect('/kasbon')->with('success', 'Data Berhasil Diupdate');
    }

    public function delete($id)
    {
        $kasbon = Kasbon::find($id);
        if ($kasbon->status == 'ACC') {
            $user = User::find($kasbon->user_id);
            $user->update(['saldo_kasbon' => $user->saldo_kasbon - $kasbon->nominal]);
        }
        $kasbon->delete();
        return redirect('/kasbon')->with('success', 'Data Berhasil di Hapus');
    }

}
