<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $limit = (int) $request->query('limit', 10);

        $items = $user->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($n) {
                return [
                    'id'      => $n->id,
                    'title'   => $n->data['title']   ?? 'Notificación',
                    'message' => $n->data['message'] ?? '',
                    'status'  => $n->data['status']  ?? 'info',
                    'email'   => $n->data['email']   ?? null,
                    'ip'      => $n->data['ip']      ?? null,
                    'ua'      => $n->data['ua']      ?? null,
                    'url'     => $n->data['url']     ?? null,
                    'read_at' => optional($n->read_at)->toIso8601String(),
                    'time'    => optional($n->created_at)->diffForHumans(),
                ];
            });

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'items'  => $items,
        ]);
    }

    public function readAll(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();
        return response()->json(['ok' => true]);
    }
}
