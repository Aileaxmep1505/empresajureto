<?php
// app/Services/MeliHttp.php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\MercadolibreAccount;
use Carbon\Carbon;

class MeliHttp {
  public static function withFreshToken() {
    $acc = MercadolibreAccount::firstOrFail();
    if ($acc->access_token_expires_at && $acc->access_token_expires_at->isFuture()) {
      return Http::withToken($acc->access_token);
    }
    $resp = Http::asForm()->post('https://api.mercadolibre.com/oauth/token', [
      'grant_type' => 'refresh_token',
      'client_id' => config('services.meli.client_id'),
      'client_secret' => config('services.meli.client_secret'),
      'refresh_token' => $acc->refresh_token,
    ]);
    $j = $resp->json();
    $acc->update([
      'access_token' => $j['access_token'],
      'refresh_token' => $j['refresh_token'] ?? $acc->refresh_token,
      'access_token_expires_at' => Carbon::now()->addSeconds($j['expires_in'] ?? 3500),
    ]);
    return Http::withToken($acc->access_token);
  }
}
