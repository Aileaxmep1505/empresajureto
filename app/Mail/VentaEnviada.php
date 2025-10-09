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

    /**
     * @param Venta $venta
     * @param string|null $asunto
     * @param string $mensajePersonalizado
     */
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
                'venta'   => $this->venta,
                'mensaje' => $this->mensajePersonalizado,
            ]);

        // === Adjuntos ===
        // 1) Factura PDF
        if (!empty($this->venta->factura_pdf_url)) {
            $this->attachSmart($this->venta->factura_pdf_url, "Factura-{$this->venta->folio}.pdf");
        }
        // 2) Factura XML
        if (!empty($this->venta->factura_xml_url)) {
            $this->attachSmart($this->venta->factura_xml_url, "Factura-{$this->venta->folio}.xml");
        }
        // 3) PDF de la venta (si tu sistema expone una URL)
        try {
            $ventaPdfUrl = route('ventas.pdf', $this->venta);
            if ($ventaPdfUrl) {
                $this->attachSmart($ventaPdfUrl, "Venta-{$this->venta->folio}.pdf");
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo adjuntar PDF de venta', [
                'venta_id' => $this->venta->id ?? null,
                'error'    => $e->getMessage(),
            ]);
        }

        return $this;
    }

    /**
     * Intenta adjuntar desde archivo local si la URL apunta a tu propio dominio/public,
     * y si no, descarga el contenido por HTTP y lo adjunta.
     */
    protected function attachSmart(string $url, string $as): void
    {
        try {
            // 1) Intento como archivo local si la URL es interna (p.ej. http://localhost/storage/...)
            $localPath = $this->mapUrlToLocalPath($url);
            if ($localPath && is_file($localPath)) {
                $this->attach($localPath, ['as' => $as]);
                return;
            }

            // 2) Fallback: descargar por HTTP y adjuntar en memoria
            $resp = Http::timeout(20)->get($url);
            if ($resp->successful()) {
                $mime = $resp->header('Content-Type') ?: 'application/octet-stream';
                $this->attachData($resp->body(), $as, ['mime' => $mime]);
            } else {
                Log::warning('No fue posible descargar adjunto', [
                    'url'    => $url,
                    'status' => $resp->status(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Fallo al adjuntar archivo', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Convierte una URL pública (p.ej. http://localhost/storage/…) a un path local
     * para adjuntarlo directamente desde disco.
     */
    protected function mapUrlToLocalPath(string $url): ?string
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['path'])) {
            return null;
        }

        $path = $parsed['path']; // e.g. /storage/facturas/uuid/file.pdf

        // Si la URL es del mismo host (app.url) o es localhost, intenta mapear a public_path('storage/...')
        $sameHost = false;
        $hostUrl  = parse_url($appUrl);
        if (!empty($hostUrl['host']) && !empty($parsed['host'])) {
            $sameHost = (strcasecmp($hostUrl['host'], $parsed['host']) === 0) || in_array($parsed['host'], ['localhost', '127.0.0.1']);
        }

        if ($sameHost || empty($parsed['host'])) {
            // public/storage -> storage/app/public (symlink)
            // Primero probamos directamente public_path, que suele bastar si tienes el symlink de 'php artisan storage:link'
            $publicCandidate = public_path(ltrim($path, '/')); // public/storage/...
            if (is_file($publicCandidate)) {
                return $publicCandidate;
            }

            // Alternativo: transformar /storage/... -> storage/app/public/...
            if (str_starts_with($path, '/storage/')) {
                $relative = substr($path, strlen('/storage/')); // facturas/uuid/file.pdf
                $storageCandidate = storage_path('app/public/' . $relative);
                if (is_file($storageCandidate)) {
                    return $storageCandidate;
                }
            }
        }

        return null;
    }
}
