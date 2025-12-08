<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CronController extends Controller
{
    /**
     * Endpoint para ejecutar agenda:run desde un cron externo.
     * GET /cron/agenda-run/{token}
     */
    public function runAgenda(Request $request, string $token)
    {
        $expected = config('app.agenda_cron_token', env('AGENDA_CRON_TOKEN'));

        // Validar token
        if (! $expected || $token !== $expected) {
            Log::warning('Cron agenda: token inválido', [
                'ip'    => $request->ip(),
                'token' => $token,
            ]);
            abort(403, 'No autorizado');
        }

        Log::info('Cron agenda: ejecución vía HTTP iniciada', [
            'ip' => $request->ip(),
        ]);

        // Ejecutar el comando de consola agenda:run
        Artisan::call('agenda:run', [
            '--limit' => 200,
        ]);

        $output = Artisan::output();

        Log::info('Cron agenda: ejecución vía HTTP terminada');

        return response()->json([
            'ok'      => true,
            'ran_at'  => now()->toDateTimeString(),
            'output'  => $output,
        ]);
    }
}
