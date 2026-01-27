<?php

namespace App\Http\Controllers;

use App\Models\TechSheet;
use App\Services\TechSheetAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
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
            'partida_number' => 'nullable|string|max:50',

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
     * Vista pública por token
     */
    public function publicShow(string $token)
    {
        $sheet = TechSheet::where('public_token', $token)->firstOrFail();

        return view('tech_sheets.public', compact('sheet'));
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
     * Descargar PDF
     */
    public function pdf(TechSheet $sheet)
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

        return $pdf->download($filename);
    }

    /**
     * Descargar Word
     */
    public function word(TechSheet $sheet)
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addTitle($sheet->product_name, 1);

        $section->addText('Marca: ' . ($sheet->brand ?? '-'));
        $section->addText('Modelo: ' . ($sheet->model ?? '-'));
        $section->addText('Referencia: ' . ($sheet->reference ?? '-'));
        $section->addTextBreak();

        if ($sheet->ai_description) {
            $section->addTitle('Descripción', 2);
            $section->addText($sheet->ai_description);
            $section->addTextBreak();
        }

        if (! empty($sheet->ai_features)) {
            $section->addTitle('Características', 2);
            foreach ($sheet->ai_features as $feat) {
                $section->addListItem($feat);
            }
            $section->addTextBreak();
        }

        if (! empty($sheet->ai_specs)) {
            $section->addTitle('Especificaciones', 2);
            foreach ($sheet->ai_specs as $spec) {
                $name  = $spec['nombre'] ?? '';
                $value = $spec['valor'] ?? '';
                $section->addText("{$name}: {$value}");
            }
        }

        $filename = 'Ficha-' . str_replace(' ', '-', $sheet->product_name) . '.docx';
        $tempPath = tempnam(sys_get_temp_dir(), 'ts_');
        $tempFile = $tempPath . '.docx';

        $phpWord->save($tempFile, 'Word2007');

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
