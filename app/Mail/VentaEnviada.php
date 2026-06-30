<?php

namespace App\Mail;

use App\Models\Venta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VentaEnviada extends Mailable
{
    use Queueable, SerializesModels;

    public Venta $venta;
    public string $mensajePersonalizado;
    public ?string $asunto;

    public function __construct(Venta $venta, ?string $asunto, string $mensajePersonalizado = '')
    {
        $this->venta = $venta;
        $this->asunto = $asunto ?: 'Documentos de su compra';
        $this->mensajePersonalizado = $mensajePersonalizado;
    }

    public function build()
    {
        $this->subject($this->asunto)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('emails.venta', [
                'venta' => $this->venta,
                'mensaje' => $this->mensajePersonalizado,
            ]);

        if (!empty($this->venta->factura_pdf_url)) {
            $this->attachSmart($this->venta->factura_pdf_url, "Factura-{$this->venta->folio}.pdf");
        }

        if (!empty($this->venta->factura_xml_url)) {
            $this->attachSmart($this->venta->factura_xml_url, "Factura-{$this->venta->folio}.xml");
        }

        try {
            if (\Illuminate\Support\Facades\Route::has('ventas.pdf')) {
                $ventaPdfUrl = route('ventas.pdf', $this->venta);

                if ($ventaPdfUrl) {
                    $this->attachSmart($ventaPdfUrl, "Venta-{$this->venta->folio}.pdf");
                }
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo adjuntar PDF de venta', [
                'venta_id' => $this->venta->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return $this;
    }

    protected function attachSmart(string $url, string $as): void
    {
        try {
            $localPath = $this->mapUrlToLocalPath($url);

            if ($localPath && is_file($localPath)) {
                $this->attach($localPath, ['as' => $as]);
                return;
            }

            $resp = Http::timeout(20)->get($url);

            if ($resp->successful()) {
                $mime = $resp->header('Content-Type') ?: 'application/octet-stream';
                $this->attachData($resp->body(), $as, ['mime' => $mime]);
            } else {
                Log::warning('No fue posible descargar adjunto', [
                    'url' => $url,
                    'status' => $resp->status(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Fallo al adjuntar archivo', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function mapUrlToLocalPath(string $url): ?string
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $parsed = parse_url($url);

        if (!$parsed || empty($parsed['path'])) {
            return null;
        }

        $path = $parsed['path'];

        $sameHost = false;
        $hostUrl = parse_url($appUrl);

        if (!empty($hostUrl['host']) && !empty($parsed['host'])) {
            $sameHost = (strcasecmp($hostUrl['host'], $parsed['host']) === 0)
                || in_array($parsed['host'], ['localhost', '127.0.0.1'], true);
        }

        if ($sameHost || empty($parsed['host'])) {
            $publicCandidate = public_path(ltrim($path, '/'));

            if (is_file($publicCandidate)) {
                return $publicCandidate;
            }

            if (str_starts_with($path, '/storage/')) {
                $relative = substr($path, strlen('/storage/'));
                $storageCandidate = storage_path('app/public/' . $relative);

                if (is_file($storageCandidate)) {
                    return $storageCandidate;
                }
            }
        }

        return null;
    }
}
