<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SkydropxProClient;

class TestSkydropxPro extends Command
{
    protected $signature = 'skydropx:pro:test {--to=06100 : CP destino}';
    protected $description = 'Verifica Skydropx PRO (OAuth2 + carriers + quotation)';

    public function handle(SkydropxProClient $sdk): int
    {
        $this->info('== Skydropx PRO (sandbox/producción) ==');
        $this->line('Base: ' . config('services.skydropx_pro.api_base'));
        $this->line('Token URL: ' . config('services.skydropx_pro.token_url'));

        // Token (para forzar cache y mostrar que haya)
        try {
            $token = $sdk->accessToken();
            $this->info('Token OK: ' . substr($token, 0, 8) . '...');
        } catch (\Throwable $e) {
            $this->error('Error token: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Carriers
        $car = $sdk->carriers();
        $this->line('Carriers HTTP ' . $car['status']);
        if ($car['ok']) {
            $names = collect(data_get($car['json'], 'data', []))->pluck('attributes.name')->filter()->values()->all();
            $this->info('Carriers: ' . (empty($names) ? '(sin nombres)' : implode(', ', $names)));
        } else {
            $this->warn(substr($car['raw'], 0, 300));
        }

        // Quotation
        $to = (string) $this->option('to');
        $q = $sdk->quote($to, ['weight' => 1, 'length' => 10, 'width' => 10, 'height' => 10]);
        $this->line('Quotation HTTP ' . $q['status']);
        if ($q['ok']) {
            $rates = collect(data_get($q['json'], 'data', data_get($q['json'], 'rates', [])));
            $this->info('Rates: ' . $rates->count());
            if ($rates->count()) {
                $first = $rates->first();
                $carrier = data_get($first, 'attributes.provider') ?? data_get($first, 'provider');
                $service = data_get($first, 'attributes.service_level_name') ?? data_get($first, 'servicelevel.name');
                $price   = data_get($first, 'attributes.total_pricing') ?? data_get($first, 'amount_local');
                $days    = data_get($first, 'attributes.delivery_days') ?? data_get($first, 'days');
                $this->line("Ejemplo: {$carrier} | {$service} | {$days}d | $".$price);
            }
        } else {
            $this->warn(substr($q['raw'], 0, 300));
        }

        return self::SUCCESS;
    }
}
