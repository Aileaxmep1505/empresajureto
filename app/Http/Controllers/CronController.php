<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CronController extends Controller
{
    public function agendaReminders(Request $request)
    {
        $expected = (string) config('cron.webhook_token');
        $received = (string) ($request->header('X-Cron-Token') ?: $request->query('token', ''));

        if ($expected === '' || !hash_equals($expected, $received)) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $exitCode = Artisan::call('agenda:send-reminders');

        return response()->json([
            'ok'        => $exitCode === 0,
            'exit_code' => $exitCode,
            'output'    => trim(Artisan::output()),
        ]);
    }
}