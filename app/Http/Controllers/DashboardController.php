<?php

namespace App\Http\Controllers;

use App\Services\DashboardAiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(DashboardAiService $dashboardAiService)
    {
        $user = auth()->user();
        $userName = $user->name ?? 'Usuario';

        $todayKey = now()->timezone(config('app.timezone', 'America/Mexico_City'))->format('Y-m-d');
        $cacheKey = 'dashboard_inspirational_phrase_' . $todayKey;

        $expiresAt = now()
            ->timezone(config('app.timezone', 'America/Mexico_City'))
            ->endOfDay();

        try {
            $inspirationalPhrase = Cache::remember($cacheKey, $expiresAt, function () use ($dashboardAiService, $userName) {
                return $dashboardAiService->getDailyInspirationalPhrase($userName);
            });

            Log::info('Dashboard IA: frase cargada en controller', [
                'cache_key' => $cacheKey,
                'phrase' => $inspirationalPhrase,
            ]);
        } catch (\Throwable $e) {
            Log::error('Dashboard IA: error en controller', [
                'message' => $e->getMessage(),
            ]);

            $inspirationalPhrase = null;
        }

        return view('dashboard', compact('inspirationalPhrase'));
    }
}