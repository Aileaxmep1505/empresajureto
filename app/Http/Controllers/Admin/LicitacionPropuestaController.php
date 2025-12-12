<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\MatchLicitacionPropuestaItems;
use App\Models\LicitacionPropuesta;
use App\Models\LicitacionPropuestaItem;
use App\Models\LicitacionRequestItem;
use App\Models\LicitacionPdf;
use App\Services\LicitacionIaService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class LicitacionPropuestaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    /**
     * Listado de propuestas económicas comparativas.
     */
    public function index(Request $request)
    {
        $query = LicitacionPropuesta::query()->latest('fecha');

        if ($request->filled('licitacion_id')) {
            $query->where('licitacion_id', $request->integer('licitacion_id'));
        }

        if ($request->filled('requisicion_id')) {
            $query->where('requisicion_id', $request->integer('requisicion_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $propuestas = $query->paginate(20);

        return view('admin.licitacion_propuestas.index', compact('propuestas'));
    }

    /**
     * Formulario para crear una nueva propuesta.
     * Llega desde "Separar PDF" pasando licitacion_pdf_id.
     */
    public function create(Request $request)
    {
        $licitacionId    = $request->integer('licitacion_id');
        $requisicionId   = $request->integer('requisicion_id');
        $licitacionPdfId = $request->integer('licitacion_pdf_id');

        $licitacionPdf = null;
        $pdfSplits     = [];

        if ($licitacionPdfId) {
            $licitacionPdf = LicitacionPdf::find($licitacionPdfId);

            if ($licitacionPdf) {
                $raw = $licitacionPdf->splits ?? [];

                if ($raw instanceof Collection) {
                    $pdfSplits = $raw->toArray();
                } elseif (is_array($raw)) {
                    $pdfSplits = $raw;
                } else {
                    $decoded   = json_decode($raw, true);
                    $pdfSplits = is_array($decoded) ? $decoded : [];
                }
            }
        }

        return view('admin.licitacion_propuestas.create', compact(
            'licitacionId',
            'requisicionId',
            'licitacionPdf',
            'licitacionPdfId',
            'pdfSplits'
        ));
    }

    /**
     * Crea una nueva Propuesta Económica Comparativa.
     * La propuesta queda ligada al PDF de licitación.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'licitacion_pdf_id' => ['required', 'integer'],
            'titulo'            => ['nullable', 'string', 'max:255'],
            'moneda'            => ['nullable', 'string', 'max:10'],
            'fecha'             => ['nullable', 'date'],
        ]);

        $licitacionPdfId = $data['licitacion_pdf_id'];
        $licitacionPdf   = LicitacionPdf::findOrFail($licitacionPdfId);

        $licitacionId  = $licitacionPdf->licitacion_id;
        $requisicionId = $licitacionPdf->requisicion_id;

        $propuesta = LicitacionPropuesta::create([
            'licitacion_id'           => $licitacionId,
            'requisicion_id'          => $requisicionId,
            'licitacion_pdf_id'       => $licitacionPdfId,
            'codigo'                  => $this->generarCodigo(),
            'titulo'                  => $data['titulo'] ?? 'Propuesta económica comparativa',
            'moneda'                  => $data['moneda'] ?? 'MXN',
            'fecha'                   => $data['fecha'] ?? now()->toDateString(),
            'status'                  => 'draft',
            'processed_split_indexes' => [],       // aún ninguno
            'merge_status'            => 'pending',
        ]);

        $routeName = null;
        if (Route::has('admin.licitacion-propuestas.show')) {
            $routeName = 'admin.licitacion-propuestas.show';
        } elseif (Route::has('licitacion-propuestas.show')) {
            $routeName = 'licitacion-propuestas.show';
        }

        if ($routeName) {
            return redirect()
                ->route($routeName, ['licitacionPropuesta' => $propuesta->id])
                ->with('status', 'Propuesta creada. Ahora procesa cada requisición con IA desde esta pantalla.');
        }

        return redirect()
            ->back()
            ->with('status', 'Propuesta creada. Ahora procesa cada requisición con IA desde esta pantalla.');
    }

    /**
     * Muestra la propuesta con su cuadro comparativo
     * y el bloque de splits (requisiciones).
     */
    public function show(LicitacionPropuesta $licitacionPropuesta)
    {
        // Cargamos también la página del renglón para la vista
        $propuesta = $licitacionPropuesta->load([
            'items.requestItem.page',
            'items.product',
            'licitacionPdf',
        ]);

        $licitacionPdf = $propuesta->licitacionPdf;

        // Normalizamos los splits igual que en create()
        $rawSplits = $licitacionPdf?->splits ?? [];

        if ($rawSplits instanceof Collection) {
            $rawSplits = $rawSplits->toArray();
        } elseif (!is_array($rawSplits)) {
            $decoded   = json_decode($rawSplits, true);
            $rawSplits = is_array($decoded) ? $decoded : [];
        }

        // Índices ya procesados
        $processed = $propuesta->processed_split_indexes ?? [];
        if ($processed instanceof Collection) {
            $processed = $processed->toArray();
        }

        $splitsInfo = [];
        foreach ($rawSplits as $idx => $split) {
            $index = $split['index'] ?? $idx;

            $splitsInfo[] = [
                'index' => $index,
                'from'  => $split['from']  ?? $split['from_page'] ?? null,
                'to'    => $split['to']    ?? $split['to_page']   ?? null,
                'pages' => $split['pages'] ?? $split['pages_count'] ?? null,
                'state' => in_array($index, $processed, true) ? 'done' : 'pending',
            ];
        }

        // true solo si TODOS los splits están en estado done
        $allSplitsProcessed = !empty($splitsInfo)
            && collect($splitsInfo)->every(fn ($s) => $s['state'] === 'done');

        return view('admin.licitacion_propuestas.show', [
            'propuesta'          => $propuesta,
            'licitacionPdf'      => $licitacionPdf,
            'splitsInfo'         => $splitsInfo,
            'allSplitsProcessed' => $allSplitsProcessed,
        ]);
    }

    /**
     * Procesa un split concreto con IA (botón "Procesar con IA").
     */
    public function processSplit(
        Request $request,
        LicitacionPropuesta $licitacionPropuesta,
        int $splitIndex,
        LicitacionIaService $iaService
    ) {
        $propuesta = $licitacionPropuesta;

        // ✅ Protección: si no hay PDF asociado, no llamamos a la IA
        if (!$propuesta->licitacion_pdf_id || !$propuesta->licitacionPdf) {
            Log::warning('Intento de procesar split sin PDF asociado', [
                'propuesta_id' => $propuesta->id,
                'split_index'  => $splitIndex,
            ]);

            return back()->with('error', 'Esta propuesta no tiene PDF de licitación asociado.');
        }

        try {
            $iaService->processSplitWithAi($propuesta, $splitIndex);

            // Marcar este split como procesado
            $processed = $propuesta->processed_split_indexes ?? [];
            if (!in_array($splitIndex, $processed, true)) {
                $processed[] = $splitIndex;
            }
            $propuesta->processed_split_indexes = $processed;
            $propuesta->save();

            // Lanzamos el match contra catálogo para toda la propuesta
            MatchLicitacionPropuestaItems::dispatch($propuesta);

            return redirect()
                ->route('admin.licitacion-propuestas.show', ['licitacionPropuesta' => $propuesta->id])
                ->with('status', "Requisición {$splitIndex} procesada con IA.");
        } catch (\Throwable $e) {
            Log::error('Error al procesar split de licitación con IA', [
                'propuesta_id' => $propuesta->id,
                'split_index'  => $splitIndex,
                'error'        => $e->getMessage(),
            ]);

            return back()->with('error', 'Ocurrió un problema al llamar a la IA. Revisa los logs.');
        }
    }

    /**
     * Hace el merge global (cuando todas las requisiciones estén procesadas).
     */
    public function mergeGlobal(Request $request, LicitacionPropuesta $licitacionPropuesta)
    {
        $propuesta = $licitacionPropuesta;

        $pdf       = $propuesta->licitacionPdf;
        $splits    = $pdf?->splits ?? [];
        $processed = $propuesta->processed_split_indexes ?? [];

        if ($splits instanceof Collection) {
            $splits = $splits->toArray();
        } elseif (!is_array($splits)) {
            $decoded = json_decode($splits, true);
            $splits  = is_array($decoded) ? $decoded : [];
        }

        if ($processed instanceof Collection) {
            $processed = $processed->toArray();
        }

        if (count($splits) === 0) {
            return back()->with('error', 'No hay splits configurados para esta licitación.');
        }

        if (count($processed) < count($splits)) {
            return back()->with('error', 'Aún faltan requisiciones por procesar antes de hacer el merge global.');
        }

        // Recalcular totales desde los renglones
        $subtotal = $propuesta->items()->sum('subtotal');
        $iva      = $subtotal * 0.16;
        $total    = $subtotal + $iva;

        $propuesta->update([
            'subtotal'     => $subtotal,
            'iva'          => $iva,
            'total'        => $total,
            'merge_status' => 'merged',
        ]);

        return back()->with('status', 'Merge global realizado. Totales actualizados.');
    }

    /**
     * Vista de edición (cambiar productos, cantidades, precios, etc.).
     */
    public function edit(LicitacionPropuesta $licitacionPropuesta)
    {
        $licitacionPropuesta->load(['items.requestItem', 'items.product']);

        return view('admin.licitacion_propuestas.edit', [
            'propuesta' => $licitacionPropuesta,
        ]);
    }

    /**
     * Actualiza datos generales de la propuesta.
     */
    public function update(Request $request, LicitacionPropuesta $licitacionPropuesta)
    {
        $data = $request->validate([
            'titulo' => ['nullable', 'string', 'max:255'],
            'moneda' => ['nullable', 'string', 'max:10'],
            'fecha'  => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $licitacionPropuesta->update($data);

        return redirect()
            ->back()
            ->with('status', 'Propuesta actualizada.');
    }

    /**
     * Elimina la propuesta y sus items.
     */
    public function destroy(LicitacionPropuesta $licitacionPropuesta)
    {
        $licitacionPropuesta->delete();

        return redirect()
            ->back()
            ->with('status', 'Propuesta eliminada.');
    }

    /**
     * Genera un código simple tipo PRO-2025-0001.
     */
    protected function generarCodigo(): string
    {
        $nextId = (LicitacionPropuesta::max('id') ?? 0) + 1;

        return 'PRO-' . now()->format('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
