<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Kategori;
use Illuminate\Http\Request;
use App\Models\Reimbursement;
use App\Models\ReimbursementsItem;
use App\Events\NotifApproval;
use App\Services\WhatsAppService;

class ReimbursementController extends Controller
{
    public function index()
    {
        $title = 'Reimbursement';
        $mulai = request()->input('mulai');
        $akhir = request()->input('akhir');
        $reimbursement = Reimbursement::when($mulai && $akhir, function ($query) use ($mulai, $akhir) {
                        $query->whereBetween('tanggal', [$mulai, $akhir]);
                    })
                    ->when(auth()->user()->is_admin == 'user', function ($query) {
                        $query->where('user_id', auth()->user()->id)
                            ->orWhereHas('items', function ($query) {
                                $query->where('user_id', auth()->user()->id);
                            });
                    })
                    ->orderBy('tanggal', 'DESC')
                    ->paginate(10)
                    ->withQueryString();

        if (auth()->user()->is_admin == 'admin') {
            return view('reimbursement.index', compact(
                'title',
                'reimbursement'
            ));
        } else {
            return view('reimbursement.indexUser', compact(
                'title',
                'reimbursement'
            ));
        }
    }

    public function tambah()
    {
        $title = 'Reimbursement';
        $user = User::orderBy('name', 'ASC')->get();
        $kategori = Kategori::orderBy('name', 'ASC')->where('active', 1)->get();
        if (auth()->user()->is_admin == 'admin') {
            return view('reimbursement.tambah', compact(
                'title',
                'user',
                'kategori',
            ));
        } else {
            return view('reimbursement.tambahUser', compact(
                'title',
                'user',
                'kategori',
            ));
        }

    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required',
            'user_id' => 'required',
            'event' => 'required',
            'kategori_id' => 'required',
            'status' => 'required',
            'jumlah' => 'required',
            'file_path' => 'required',
            'qty' => 'required',
            'total' => 'required',
            'sisa' => 'required',
        ]);

        $validated['jumlah'] = str_replace(',', '', $validated['jumlah']);
        $validated['total'] = str_replace(',', '', $validated['total']);
        $validated['sisa'] = str_replace(',', '', $validated['sisa']);

        if ($request->file('file_path')) {
            $validated['file_path'] = $request->file('file_path')->store('file_path');
            $validated['file_name'] = $request->file('file_path')->getClientOriginalName();
        }

        $reimbursement = Reimbursement::create($validated);

        $user_id_item = $request->input('user_id_item', []);
        $fee = $request->input('fee', []);

        for ($i = 0; $i < count($user_id_item); $i++) {
            ReimbursementsItem::create([
                'reimbursement_id' => $reimbursement->id,
                'user_id'  => $user_id_item[$i],
                'fee' => $fee[$i] ? str_replace(',', '', $fee[$i]) : 0,
            ]);
        }

        // Kirim notifikasi ke admin/HRD/GM/Finance jika user mengajukan reimbursement
        if (strtolower($validated['status']) == 'pending') {
            $user_pengaju = User::find($validated['user_id']);
            $total_formatted = number_format($reimbursement->total, 0, ',', '.');
            
            $admin_users = User::where('is_admin', 'admin')->get();

            foreach ($admin_users as $user) {
                $type = 'Approval';
                $notif = 'Pengajuan Reimbursement Rp ' . $total_formatted . ' (' . $reimbursement->event . ') dari ' . $user_pengaju->name . ' menunggu persetujuan Anda';
                $url = url('/reimbursement');

                try {
                    $user->messages = [
                        'user_id'   =>  $user_pengaju->id,
                        'from'      =>  $user_pengaju->name,
                        'message'   =>  $notif,
                        'action'    =>  '/reimbursement'
                    ];
                    $user->notify(new \App\Notifications\UserNotification);
                } catch (\Exception $e) {
                    \Log::error('Reimbursement notification error: ' . $e->getMessage());
                }

                try {
                    NotifApproval::dispatch($type, $user->id, $notif, $url);
                } catch (\Exception $e) {
                    \Log::error('NotifApproval error: ' . $e->getMessage());
                }

                try {
                    WhatsAppService::send($user->telepon, $notif . "\n" . $url);
                } catch (\Exception $e) {
                    \Log::error('WhatsApp error: ' . $e->getMessage());
                }
            }
        }

        return redirect('/reimbursement')->with('success', 'Data Berhasil Disimpan');
    }

    public function edit($id)
    {
        $reimbursement = Reimbursement::find($id);
        $title = 'Reimbursement';
        $user = User::orderBy('name', 'ASC')->get();
        $kategori = Kategori::orderBy('name', 'ASC')->where('active', 1)->get();
        if (auth()->user()->is_admin == 'admin') {
            return view('reimbursement.edit', compact(
                'title',
                'user',
                'kategori',
                'reimbursement',
            ));
        } else {
            return view('reimbursement.editUser', compact(
                'title',
                'user',
                'kategori',
                'reimbursement',
            ));
        }

    }

    public function update(Request $request, $id)
    {
        $reimbursement = Reimbursement::find($id);
        $validated = $request->validate([
            'tanggal' => 'required',
            'user_id' => 'required',
            'event' => 'required',
            'kategori_id' => 'required',
            'status' => 'required',
            'jumlah' => 'required',
            'file_path' => 'nullable',
            'qty' => 'required',
            'total' => 'required',
            'sisa' => 'required',
        ]);

        $validated['jumlah'] = str_replace(',', '', $validated['jumlah']);
        $validated['total'] = str_replace(',', '', $validated['total']);
        $validated['sisa'] = str_replace(',', '', $validated['sisa']);

        if ($request->file('file_path')) {
            $validated['file_path'] = $request->file('file_path')->store('file_path');
            $validated['file_name'] = $request->file('file_path')->getClientOriginalName();
        }

        $reimbursement->update($validated);

        $user_id_item = $request->input('user_id_item', []);
        $fee = $request->input('fee', []);

        ReimbursementsItem::where('reimbursement_id', $reimbursement->id)->delete();
        for ($i = 0; $i < count($user_id_item); $i++) {
            ReimbursementsItem::create([
                'reimbursement_id' => $reimbursement->id,
                'user_id'  => $user_id_item[$i],
                'fee' => $fee[$i] ? str_replace(',', '', $fee[$i]) : 0,
            ]);
        }

        return redirect('/reimbursement')->with('success', 'Data Berhasil Diupdate');
    }

    public function approval(Request $request, $id)
    {
        $reimbursement = Reimbursement::find($id);
        $old_status = $reimbursement->status;
        
        $validated = $request->validate([
            'status' => 'required',
        ]);
        $reimbursement->update($validated);
        
        // Kirim notifikasi ke user saat status berubah dari Pending
        if (strtolower($old_status) == 'pending' && strtolower($validated['status']) != 'pending') {
            $user_reimbursement = User::find($reimbursement->user_id);
            $total_formatted = number_format($reimbursement->total, 0, ',', '.');
            
            if ($validated['status'] == 'Approved') {
                $type = 'Approved';
                $notif = 'Pengajuan Reimbursement Rp ' . $total_formatted . ' (' . $reimbursement->event . ') Anda telah DISETUJUI oleh ' . auth()->user()->name;
            } else {
                $type = 'Rejected';
                $notif = 'Pengajuan Reimbursement Rp ' . $total_formatted . ' (' . $reimbursement->event . ') Anda telah DITOLAK oleh ' . auth()->user()->name;
            }
            
            $url = url('/reimbursement');

            try {
                $user_reimbursement->messages = [
                    'user_id'   =>  auth()->user()->id,
                    'from'      =>  auth()->user()->name,
                    'message'   =>  $notif,
                    'action'    =>  '/reimbursement'
                ];
                $user_reimbursement->notify(new \App\Notifications\UserNotification);
            } catch (\Exception $e) {
                \Log::error('Reimbursement approval notification error: ' . $e->getMessage());
            }

            try {
                NotifApproval::dispatch($type, $user_reimbursement->id, $notif, $url);
            } catch (\Exception $e) {
                \Log::error('NotifApproval error: ' . $e->getMessage());
            }

            try {
                WhatsAppService::send($user_reimbursement->telepon, $notif . "\n" . $url);
            } catch (\Exception $e) {
                \Log::error('WhatsApp error: ' . $e->getMessage());
            }
        }
        
        return redirect('/reimbursement')->with('success', 'Data Berhasil Diupdate');
    }


    public function delete($id)
    {
        $reimbursement = Reimbursement::find($id);
        $reimbursement->delete();
        return redirect('/reimbursement')->with('success', 'Data Berhasil Didelete');
    }

    public function getKategori(Request $request)
    {
        return Kategori::find($request->kategori_id);
    }

}
