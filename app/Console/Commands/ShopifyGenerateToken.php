<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ShopifyGenerateToken extends Command
{
    protected $signature = 'shopify:generate-token';

    protected $description = 'Genera un Admin API access token para Shopify usando Client Credentials';

    public function handle(): int
    {
        $shop = config('services.shopify.shop');
        $clientId = config('services.shopify.client_id');
        $clientSecret = config('services.shopify.client_secret');

        if (!$shop || !$clientId || !$clientSecret) {
            $this->error('Faltan SHOPIFY_SHOP, SHOPIFY_CLIENT_ID o SHOPIFY_CLIENT_SECRET en .env');
            return self::FAILURE;
        }

        $url = "https://{$shop}/admin/oauth/access_token";

        $response = Http::asForm()->post($url, [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if (!$response->successful()) {
            $this->error('Shopify respondió error HTTP: ' . $response->status());
            $this->line($response->body());
            return self::FAILURE;
        }

        $json = $response->json();

        $token = $json['access_token'] ?? null;

        if (!$token) {
            $this->error('Shopify no regresó access_token.');
            $this->line(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::FAILURE;
        }

        $this->info('TOKEN GENERADO CORRECTAMENTE');
        $this->line('');
        $this->line('Copia este token en tu .env:');
        $this->line('');
        $this->line('SHOPIFY_ADMIN_TOKEN=' . $token);

        return self::SUCCESS;
    }
}