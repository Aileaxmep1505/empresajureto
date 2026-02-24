<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WhatsAppCloud;
use Illuminate\Http\Client\RequestException;

class WhatsAppDiagnose extends Command
{
    protected $signature = 'wa:diag';
    protected $description = 'Diagnóstico WhatsApp Cloud API: números en WABA, consistencia y apps suscritas';

    public function handle(WhatsAppCloud $wa)
    {
        $waba = (string) config('whatsapp.waba_id');
        $pnid = (string) config('whatsapp.phone_number_id');

        if (!$waba || !$pnid) {
            $this->error('Faltan WHATSAPP_BUSINESS_ACCOUNT_ID o WHATSAPP_PHONE_NUMBER_ID en .env');
            return self::FAILURE;
        }

        $this->info("API Version: " . config('whatsapp.version'));
        $this->info("WABA: $waba");
        $this->info("PhoneNumberId (Jureto): $pnid");
        $this->line(str_repeat('-', 70));

        try {
            // 1) Listar números en el WABA (esto define si están “vinculados”)
            $nums = $wa->listWabaPhoneNumbers($waba);
            $data = (array) data_get($nums, 'data', []);

            $this->info("NÚMEROS EN ESTE WABA:");
            if (count($data) === 0) {
                $this->warn(" - (vacío) No se encontraron números en /$waba/phone_numbers (revisa token/permisos).");
            } else {
                foreach ($data as $i => $n) {
                    $this->line(sprintf(
                        " - [%d] id=%s | phone=%s | name=%s | status=%s | quality=%s",
                        $i + 1,
                        $n['id'] ?? 'N/A',
                        $n['display_phone_number'] ?? 'N/A',
                        $n['verified_name'] ?? 'N/A',
                        $n['status'] ?? 'N/A',
                        $n['quality_rating'] ?? 'N/A',
                    ));
                }
            }

            $this->line(str_repeat('-', 70));

            // 2) Validar que tu phone_number_id (Jureto) esté dentro del WABA
            $exists = collect($data)->contains(fn($n) => (string)($n['id'] ?? '') === (string)$pnid);

            if ($exists) {
                $this->info("✅ Tu WHATSAPP_PHONE_NUMBER_ID SÍ pertenece a este WABA.");
            } else {
                $this->warn("⚠️ Tu WHATSAPP_PHONE_NUMBER_ID NO aparece dentro de este WABA.");
                $this->warn("   => Puede que tu .env tenga WABA equivocado, o el token no es de ese WABA.");
            }

            // 3) Conclusión “vinculados”
            if (count($data) > 1) {
                $this->warn("⚠️ Este WABA tiene MÁS DE 1 número. Ahí es donde están 'vinculados' de verdad.");
            } elseif (count($data) === 1 && $exists) {
                $this->info("✅ Este WABA parece tener SOLO el número de Jureto.");
            }

            $this->line(str_repeat('-', 70));

            // 4) Info básica del número (opcional, para confirmar nombre/estado)
            $phone = $wa->getPhoneNumberBasic($pnid);
            $this->info("INFO BÁSICA DEL NÚMERO (Jureto):");
            $this->line(" - display_phone_number: " . (data_get($phone, 'display_phone_number') ?? 'N/A'));
            $this->line(" - verified_name: " . (data_get($phone, 'verified_name') ?? 'N/A'));
            $this->line(" - status: " . (data_get($phone, 'status') ?? 'N/A'));
            $this->line(" - quality_rating: " . (data_get($phone, 'quality_rating') ?? 'N/A'));

            $this->line(str_repeat('-', 70));

            // 5) Apps suscritas al WABA
            $apps = $wa->listSubscribedApps($waba);
            $appsData = (array) data_get($apps, 'data', []);

            $this->info("APPS SUSCRITAS A ESTE WABA:");
            if (count($appsData) === 0) {
                $this->warn(" - (vacío) No se ven apps suscritas (puede ser permisos/token).");
            } else {
                foreach ($appsData as $a) {
                    $this->line(" - app_id=" . ($a['id'] ?? 'N/A') . " | name=" . ($a['name'] ?? 'N/A'));
                }
            }

            return self::SUCCESS;

        } catch (RequestException $e) {
            $this->error("ERROR HTTP: " . $e->getMessage());
            $body = optional($e->response)->json();
            if ($body) {
                $this->line("Respuesta:");
                $this->line(json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error("ERROR: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}