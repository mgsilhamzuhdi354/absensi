<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $filter = request()->input('filter','');
        $title = 'Notifications';

        $inboxs = auth()->user()->notifications()
                        ->when($filter == 'read', fn($query)=>$query->whereNotNull('read_at'))
                        ->when($filter == 'unread', fn($query)=>$query->whereNull('read_at'))
                        ->paginate(10)
                        ->withQueryString();

        if (auth()->user()->is_admin == 'admin') {
            return view('notifications.indexAdmin', compact(
                'inboxs',
                'title'
            ));
        } else {
            return view('notifications.index', compact(
                'inboxs',
                'title'
            ));
        }
    }

    public function read()
    {
        $filter = request()->input('filter','');
        $title = 'Notifications';

        $inboxs = auth()->user()->notifications()
                        ->whereNotNull('read_at')
                        ->paginate(10)
                        ->withQueryString();

        return view('notifications.indexAdmin', compact(
            'inboxs',
            'title'
        ));
    }

    public function unread()
    {
        $filter = request()->input('filter','');
        $title = 'Notifications';

        $inboxs = auth()->user()->notifications()
                        ->whereNull('read_at')
                        ->paginate(10)
                        ->withQueryString();

        return view('notifications.indexAdmin', compact(
            'inboxs',
            'title'
        ));
    }

    public function readMessage($id)
    {
        $notifikasi = auth()->user()->notifications()->where('id', $id)->first();
        
        if (!$notifikasi) {
            return redirect('/notifications')->with('error', 'Notifikasi tidak ditemukan');
        }
        
        // Mark as read if not already read
        if ($notifikasi->read_at === null) {
            $notifikasi->markAsRead();
        }
        
        $action = $notifikasi->data["action"] ?? '/dashboard';
        return redirect($action);
    }

    /**
     * API endpoint untuk memeriksa notifikasi baru (untuk polling)
     * Parameter: since (timestamp untuk filter notifikasi baru setelah waktu tersebut)
     */
    public function checkNew()
    {
        $since = request()->input('since');
        
        $query = auth()->user()->notifications()
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc');
        
        // Jika ada parameter 'since', filter hanya notifikasi baru setelah waktu tersebut
        if ($since) {
            $sinceTime = \Carbon\Carbon::createFromTimestamp($since);
            $query->where('created_at', '>', $sinceTime);
        }
        
        $unreadNotifications = $query->take(5)->get();
        
        $notifications = $unreadNotifications->map(function($notif) {
            return [
                'id' => $notif->id,
                'message' => $notif->data['message'] ?? '',
                'from' => $notif->data['from'] ?? '',
                'action' => $notif->data['action'] ?? '/notifications',
                'created_at' => $notif->created_at->diffForHumans(),
                'timestamp' => $notif->created_at->timestamp,
            ];
        });

        // Total unread count (semua yang belum dibaca)
        $totalUnread = auth()->user()->notifications()->whereNull('read_at')->count();

        return response()->json([
            'count' => $totalUnread,
            'new_count' => $unreadNotifications->count(),
            'notifications' => $notifications,
            'server_time' => now()->timestamp
        ]);
    }

    /**
     * Delete a single notification
     */
    public function delete($id)
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();
        
        if (!$notification) {
            return redirect('/notifications')->with('error', 'Notifikasi tidak ditemukan');
        }
        
        $notification->delete();
        
        return redirect('/notifications')->with('success', 'Notifikasi berhasil dihapus');
    }

    /**
     * Delete all notifications for current user
     */
    public function deleteAll()
    {
        auth()->user()->notifications()->delete();
        
        return redirect('/notifications')->with('success', 'Semua notifikasi berhasil dihapus');
    }

    /**
     * Delete all read notifications for current user
     */
    public function deleteRead()
    {
        auth()->user()->notifications()->whereNotNull('read_at')->delete();
        
        return redirect('/notifications')->with('success', 'Semua notifikasi yang sudah dibaca berhasil dihapus');
    }

}

