<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Feed JSON para el panel de notificaciones.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $limit = (int) $request->integer('limit', 30);
        if ($limit < 5 || $limit > 100) {
            $limit = 30;
        }

        $collection = $user->notifications()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $items = $collection->map(function ($n) {
            $data = $n->data ?? [];

            $status = $data['status'] ?? 'info';

            return [
                'id'      => $n->id,
                'title'   => $data['title']   ?? 'Notificación',
                'message' => $data['message'] ?? '',
                'status'  => $status, // info | warn | error
                'time'    => optional($n->created_at)->diffForHumans(),
                'read_at' => $n->read_at ? $n->read_at->toIso8601String() : null,

                // 👈 AQUÍ VA LA URL PLANA QUE LEE EL JS
                'url'     => $data['url'] ?? null,
            ];
        });

        return response()->json([
            'ok'     => true,
            'items'  => $items,
            'unread' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Marcar todas como leídas.
     */
    public function readAll(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Marcar UNA notificación como leída.
     */
    public function readOne(Request $request, DatabaseNotification $notification)
    {
        $user = $request->user();
        abort_unless($user && $notification->notifiable_id === $user->id, 403);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json(['ok' => true]);
    }
}
