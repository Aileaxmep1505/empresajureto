<?php

namespace App\Http\Controllers;

use App\Models\TechSheet;
use App\Services\TechSheetAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TechSheetController extends Controller
{
    /**
     * Listado de fichas técnicas
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $items = TechSheet::query()
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($qq) use ($q) {
                    $qq->where('product_name', 'like', "%{$q}%")
                       ->orWhere('brand', 'like', "%{$q}%")
                       ->orWhere('model', 'like', "%{$q}%")
                       ->orWhere('reference', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('tech_sheets.index', [
            'items' => $items,
            'q'     => $q,
        ]);
    }

    /**
     * Formulario de creación
     */
    public function create()
    {
        return view('tech_sheets.create');
    }

    /**
     * Guardar y llamar a la IA
     */
    public function store(Request $request, TechSheetAiService $ai)
    {
        $data = $request->validate([
            'product_name'      => 'required|string|max:255',
            'user_description'  => 'nullable|string',
            'brand'             => 'nullable|string|max:255',
            'model'             => 'nullable|string|max:255',
            'reference'         => 'nullable|string|max:255',
            'identification'    => 'nullable|string|max:255',
            'image'             => 'nullable|image|max:4096',
            'brand_image'       => 'nullable|image|max:4096',
            'partida_number'    => 'nullable|string|max:50',
        ]);

        // Imagen principal del producto
        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('tech_sheets', 'public');
        }
        $data['image_path'] = $path;

        // Imagen / logo de la marca
        $brandPath = null;
        if ($request->hasFile('brand_image')) {
            $brandPath = $request->file('brand_image')->store('tech_sheets/brands', 'public');
        }
        $data['brand_image_path'] = $brandPath;

        // Llamar IA
        $aiData = $ai->generate($data) ?? [
            'ai_description' => null,
            'ai_features'    => [],
            'ai_specs'       => [],
        ];

        // Crear ficha
        $sheet = TechSheet::create(array_merge($data, $aiData));

        // Token público (para link + QR)
        if (empty($sheet->public_token)) {
            $sheet->public_token = (string) Str::uuid();
            $sheet->save();
        }

        return redirect()
            ->route('tech-sheets.show', $sheet)
            ->with('status', 'Ficha técnica generada con IA.');
    }

    /**
     * Ver una ficha (vista interna, con link público listo)
     */
    public function show(TechSheet $sheet)
    {
        $publicUrl = $sheet->public_token
            ? route('tech-sheets.public', $sheet->public_token)
            : null;

        return view('tech_sheets.show', [
            'sheet'     => $sheet,
            'publicUrl' => $publicUrl,
        ]);
    }

    /**
     * Formulario de edición
     */
    public function edit(TechSheet $sheet)
    {
        return view('tech_sheets.edit', [
            'sheet' => $sheet,
        ]);
    }

    /**
     * Actualizar ficha + subir PDFs + elegir PDF activo (solo para el botón Ⓟ)
     */
    public function update(Request $request, TechSheet $sheet)
    {
        $data = $request->validate([
            'product_name'      => 'required|string|max:255',
            'user_description'  => 'nullable|string',
            'brand'             => 'nullable|string|max:255',
            'model'             => 'nullable|string|max:255',
            'reference'         => 'nullable|string|max:255',
            'identification'    => 'nullable|string|max:255',
            'partida_number'    => 'nullable|string|max:50',

            // PDFs ligados
            'brand_pdf'         => 'nullable|file|mimes:pdf|max:25600',
            'custom_pdf'        => 'nullable|file|mimes:pdf|max:25600',

            // selector: brand | custom | generated
            'active_pdf'        => 'nullable|in:brand,custom,generated',
        ]);

        // ===== Campos básicos =====
        $sheet->product_name     = $data['product_name'];
        $sheet->user_description = $data['user_description'] ?? null;
        $sheet->brand            = $data['brand'] ?? null;
        $sheet->model            = $data['model'] ?? null;
        $sheet->reference        = $data['reference'] ?? null;
        $sheet->identification   = $data['identification'] ?? null;
        $sheet->partida_number   = $data['partida_number'] ?? null;

        // ===== Selector PDF principal =====
        // 'generated' => null (usa el generado)
        if (array_key_exists('active_pdf', $data)) {
            $sheet->active_pdf = ($data['active_pdf'] === 'generated') ? null : $data['active_pdf'];
        }

        // ===== Subir PDFs =====
        $baseDir = "tech_sheets/{$sheet->id}/pdfs";

        if ($request->hasFile('brand_pdf')) {
            // NOTA: con este esquema se reemplaza el de marca (solo 1 ruta)
            if ($sheet->brand_pdf_path) {
                Storage::disk('public')->delete($sheet->brand_pdf_path);
            }

            $sheet->brand_pdf_path = $request->file('brand_pdf')->storeAs(
                $baseDir,
                'marca.pdf',
                'public'
            );

            if ($sheet->active_pdf === null) {
                $sheet->active_pdf = 'brand';
            }
        }

        if ($request->hasFile('custom_pdf')) {
            // NOTA: con este esquema se reemplaza el tuyo (solo 1 ruta)
            if ($sheet->custom_pdf_path) {
                Storage::disk('public')->delete($sheet->custom_pdf_path);
            }

            $sheet->custom_pdf_path = $request->file('custom_pdf')->storeAs(
                $baseDir,
                'mio.pdf',
                'public'
            );

            if ($sheet->active_pdf === null) {
                $sheet->active_pdf = 'custom';
            }
        }

        // ===== Validación extra: no permitir activo que no existe =====
        if ($sheet->active_pdf === 'brand' && empty($sheet->brand_pdf_path)) {
            $sheet->active_pdf = null;
        }
        if ($sheet->active_pdf === 'custom' && empty($sheet->custom_pdf_path)) {
            $sheet->active_pdf = null;
        }

        // Token público si faltaba
        if (empty($sheet->public_token)) {
            $sheet->public_token = (string) Str::uuid();
        }

        $sheet->save();

        return redirect()
            ->route('tech-sheets.edit', $sheet)
            ->with('ok', 'Ficha técnica actualizada.');
    }

    /**
     * ✅ (Opcional) Borrar SOLO un PDF subido (marca o mío), sin borrar la ficha.
     * Útil si en la vista pones un botón "Eliminar PDF".
     *
     * $type: 'brand' | 'custom'
     */
    public function deletePdf(Request $request, TechSheet $sheet, string $type)
    {
        abort_unless(in_array($type, ['brand', 'custom'], true), 404);

        if ($type === 'brand') {
            if ($sheet->brand_pdf_path) {
                Storage::disk('public')->delete($sheet->brand_pdf_path);
            }
            $sheet->brand_pdf_path = null;

            if ($sheet->active_pdf === 'brand') {
                $sheet->active_pdf = null;
            }
        }

        if ($type === 'custom') {
            if ($sheet->custom_pdf_path) {
                Storage::disk('public')->delete($sheet->custom_pdf_path);
            }
            $sheet->custom_pdf_path = null;

            if ($sheet->active_pdf === 'custom') {
                $sheet->active_pdf = null;
            }
        }

        $sheet->save();

        return back()->with('ok', 'PDF eliminado.');
    }

    /**
     * ✅ Destroy: borra ficha + archivos relacionados
     */
    public function destroy(TechSheet $sheet)
    {
        // Borrar archivos sueltos si existen
        $disk = Storage::disk('public');

        if ($sheet->image_path) {
            $disk->delete($sheet->image_path);
        }

        if ($sheet->brand_image_path) {
            $disk->delete($sheet->brand_image_path);
        }

        if ($sheet->brand_pdf_path) {
            $disk->delete($sheet->brand_pdf_path);
        }

        if ($sheet->custom_pdf_path) {
            $disk->delete($sheet->custom_pdf_path);
        }

        // Borrar carpeta de la ficha (por si quedaron archivos)
        $disk->deleteDirectory("tech_sheets/{$sheet->id}");

        // Borrar registro
        $sheet->delete();

        return redirect()
            ->route('tech-sheets.index')
            ->with('ok', 'Ficha técnica eliminada.');
    }

    /**
     * Vista pública por token
     */
    public function publicShow(string $token)
    {
        $sheet = TechSheet::where('public_token', $token)->firstOrFail();

        return view('tech_sheets.public', [
            'sheet' => $sheet,
        ]);
    }

    /**
     * QR PNG de la ficha pública
     */
    public function qr(string $token)
    {
        $sheet = TechSheet::where('public_token', $token)->firstOrFail();

        $url = route('tech-sheets.public', $sheet->public_token);

        $png = QrCode::format('png')
            ->size(400)
            ->margin(1)
            ->generate($url);

        return response($png)->header('Content-Type', 'image/png');
    }

    /**
     * Helper: genera el PDF del sistema (IA) para VERLO en navegador.
     */
    private function generatedPdfStream(TechSheet $sheet)
    {
        $publicUrl = $sheet->public_token
            ? route('tech-sheets.public', $sheet->public_token)
            : null;

        $pdf = Pdf::loadView('tech_sheets.pdf', [
                'sheet'     => $sheet,
                'publicUrl' => $publicUrl,
            ])
            ->setPaper('letter', 'portrait');

        $filename = 'Ficha-' . str_replace(' ', '-', $sheet->product_name) . '.pdf';

        return $pdf->stream($filename);
    }

    /**
     * ✅ ver SIEMPRE el PDF generado (IA/sistema),
     * aunque existan PDFs subidos.
     */
    public function pdfGenerated(TechSheet $sheet)
    {
        return $this->generatedPdfStream($sheet);
    }

    /**
     * PDF principal (botón Ⓟ):
     * - si active_pdf = brand/custom y existe, abre ese
     * - si no, abre el generado
     */
    public function pdf(TechSheet $sheet)
    {
        if ($sheet->active_pdf === 'custom' && $sheet->custom_pdf_path) {
            if (Storage::disk('public')->exists($sheet->custom_pdf_path)) {
                return response()->file(Storage::disk('public')->path($sheet->custom_pdf_path));
            }
        }

        if ($sheet->active_pdf === 'brand' && $sheet->brand_pdf_path) {
            if (Storage::disk('public')->exists($sheet->brand_pdf_path)) {
                return response()->file(Storage::disk('public')->path($sheet->brand_pdf_path));
            }
        }

        return $this->generatedPdfStream($sheet);
    }
}
