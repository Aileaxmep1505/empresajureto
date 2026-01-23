<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Milon\Barcode\DNS1D;

class CatalogPublicController extends Controller
{
    /**
     * Ficha / hoja de producto (web).
     * URL: /p/{catalogItem}
     */
    public function preview(CatalogItem $catalogItem)
    {
        // Fotos base: las pasamos al blade y ahí se acomodan
        $photos = array_values(array_filter([
            $catalogItem->image_url,
            $catalogItem->photo_1,
            $catalogItem->photo_2,
            $catalogItem->photo_3,
        ]));

        return view('admin.catalog.preview', [
            'item'   => $catalogItem,
            'photos' => $photos,
        ]);
    }

    /**
     * QR del producto en SVG (para usarlo como <img src="..."> en la ficha web).
     * Ruta: GET /p/{catalogItem}/qr  -> name: catalog.qr
     */
    public function qr(CatalogItem $catalogItem)
    {
        $url = route('catalog.preview', $catalogItem);

        $svg = QrCode::format('svg')
            ->size(220)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($url);

        return response($svg, 200, [
            'Content-Type'  => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Etiqueta QR 2x2" para impresora térmica (PDF).
     * Ruta: GET /p/{catalogItem}/qr-label  -> name: catalog.qr.label
     */
    public function qrLabel(CatalogItem $catalogItem)
    {
        // URL que abrirá el QR (la ficha bonita)
        $url = route('catalog.preview', $catalogItem);

        // SVG del QR
        $svg = QrCode::format('svg')
            ->size(280)        // tamaño del QR dentro del SVG
            ->margin(0)
            ->errorCorrection('M')
            ->generate($url);

        // Data URI para que DomPDF lo pinte sin problemas
        $qrBase64 = 'data:image/svg+xml;base64,' . base64_encode($svg);

        // Textos recortados para que quepan en 2x2"
        $name  = Str::limit($catalogItem->name ?? '', 38, '…');
        $brand = trim((string) ($catalogItem->brand_name ?? ''));
        $model = trim((string) ($catalogItem->model_name ?? ''));

        $brandLine = trim(
            ($brand !== '' ? $brand : '') .
            ($brand !== '' && $model !== '' ? ' · ' : '') .
            ($model !== '' ? $model : '')
        );

        // PDF 2x2 pulgadas (72 pt * 2 = 144 pt)
        $pdf = Pdf::loadView('admin.catalog.qr_label', [
                'item'      => $catalogItem,
                'qrBase64'  => $qrBase64,
                'name'      => $name,
                'brandLine' => $brandLine,
            ])
            ->setPaper([0, 0, 144, 144], 'portrait');

        return $pdf->stream('etiqueta-qr-'.$catalogItem->id.'.pdf');
        // return $pdf->download('etiqueta-qr-'.$catalogItem->id.'.pdf');
    }

    /**
     * Código de barras en SVG (para el toggle en la ficha web).
     * Ruta: GET /p/{catalogItem}/barcode -> name: catalog.barcode
     */
    public function barcode(CatalogItem $catalogItem)
    {
        $code = $this->codeValue($catalogItem);

        $dns = new DNS1D();
        $dns->setStorPath(storage_path('framework/barcodes/'));

        // C128 estándar logístico
        $svg = $dns->getBarcodeSVG($code, 'C128', 1.6, 60, 'black', true);

        return response($svg, 200, [
            'Content-Type'  => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Etiqueta 2x1" con código de barras (PDF).
     * Ruta: GET /p/{catalogItem}/barcode-label -> name: catalog.barcode.label
     */
 public function barcodeLabel(CatalogItem $catalogItem)
{
    $code  = $this->codeValue($catalogItem);
    $name  = Str::limit($catalogItem->name ?? '', 32, '…');
    $brand = trim((string) ($catalogItem->brand_name ?? ''));
    $model = trim((string) ($catalogItem->model_name ?? ''));

    $brandLine = trim(
        ($brand !== '' ? $brand : '') .
        ($brand !== '' && $model !== '' ? ' · ' : '') .
        ($model !== '' ? $model : '')
    );

    // Generar SVG del código de barras
    $dns = new \Milon\Barcode\DNS1D();
    $dns->setStorPath(storage_path('framework/barcodes/'));

    // 👇 ÚNICO CAMBIO IMPORTANTE: último parámetro en FALSE para que NO pinte el texto
    $svg = $dns->getBarcodeSVG($code, 'C128', 1.4, 48, 'black', false);

    // Lo convertimos a base64 para usarlo como <img src="...">
    $barcodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($svg);

    $pdf = Pdf::loadView('admin.catalog.barcode_label', [
            'item'          => $catalogItem,
            'name'          => $name,
            'brandLine'     => $brandLine,
            'code'          => $code,
            'barcodeBase64' => $barcodeBase64,
        ])
        // 2x1 pulgadas → 144x72 pt
        ->setPaper([0, 0, 144, 72]);

    return $pdf->stream('etiqueta-barcode-'.$catalogItem->id.'.pdf');
    // return $pdf->download('etiqueta-barcode-'.$catalogItem->id.'.pdf');
}

    /**
     * Helper: qué valor se usa para GTIN/sku/ID en QR y barras.
     */
    protected function codeValue(CatalogItem $item): string
    {
        $gtin = trim((string) ($item->meli_gtin ?? ''));
        $sku  = trim((string) ($item->sku ?? ''));

        if ($gtin !== '') return $gtin;
        if ($sku  !== '') return $sku;

        // fallback: ID con padding
        return str_pad((string) $item->id, 8, '0', STR_PAD_LEFT);
    }
}
