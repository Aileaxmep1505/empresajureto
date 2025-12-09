<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Feed JSON para el panel (campanita)
     */
    public function index(Request $request)
    {
        $user  = $request->user();
        $limit = (int) $request->query('limit', 15);

        $items = $user->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($n) {
                $data = is_array($n->data) ? $n->data : (array) $n->data;

                return [
                    'id'      => $n->id,
                    'title'   => $data['title']   ?? 'Notificación',
                    'message' => $data['message'] ?? '',
                    'status'  => $data['status']  ?? 'info', // info|warn|error
                    'read_at' => optional($n->read_at)->toIso8601String(),
                    'time'    => optional($n->created_at)->diffForHumans(),
                ];
            });

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'items'  => $items,
        ]);
    }

    /**
     * Marcar TODAS como leídas
     */
    public function readAll(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }

    /**
     * Marcar UNA notificación como leída
     */
    public function readOne(Request $request, string $notificationId)
    {
        $user  = $request->user();
        $notif = $user->notifications()->where('id', $notificationId)->firstOrFail();

        if (is_null($notif->read_at)) {
            $notif->markAsRead();
        }

        return response()->json(['ok' => true]);
    }
}
