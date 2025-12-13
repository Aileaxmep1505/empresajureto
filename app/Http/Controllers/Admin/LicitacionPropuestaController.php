<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\MatchLicitacionPropuestaItems;
use App\Models\LicitacionPropuesta;
use App\Models\LicitacionPropuestaItem;
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
        return [ new Middleware('auth') ];
    }

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

    public function create(Request $request)
    {
        $licitacionId    = $request->integer('licitacion_id');
        $requisicionId   = $request->integer('requisicion_id');
        $licitacionPdfId = $request->integer('licitacion_pdf_id');

        $licitacionPdf = $licitacionPdfId ? LicitacionPdf::find($licitacionPdfId) : null;

        $pdfSplits = [];
        if ($licitacionPdf) {
            $meta = $licitacionPdf->meta ?? [];
            $pdfSplits = $meta['splits'] ?? [];
            if ($pdfSplits instanceof Collection) $pdfSplits = $pdfSplits->toArray();
            if (!is_array($pdfSplits)) $pdfSplits = [];
        }

        return view('admin.licitacion_propuestas.create', compact(
            'licitacionId',
            'requisicionId',
            'licitacionPdf',
            'licitacionPdfId',
            'pdfSplits'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'licitacion_pdf_id' => ['required', 'integer'],
            'titulo'            => ['nullable', 'string', 'max:255'],
            'moneda'            => ['nullable', 'string', 'max:10'],
            'fecha'             => ['nullable', 'date'],
        ]);

        $pdf = LicitacionPdf::findOrFail($data['licitacion_pdf_id']);

        $propuesta = LicitacionPropuesta::create([
            'licitacion_id'           => $pdf->licitacion_id,
            'requisicion_id'          => $pdf->requisicion_id,
            'licitacion_pdf_id'       => $pdf->id,
            'codigo'                  => $this->generarCodigo(),
            'titulo'                  => $data['titulo'] ?? 'Propuesta económica comparativa',
            'moneda'                  => $data['moneda'] ?? 'MXN',
            'fecha'                   => $data['fecha'] ?? now()->toDateString(),
            'status'                  => 'draft',
            'processed_split_indexes' => [],
            'merge_status'            => 'pending',
            'subtotal'                => 0,
            'iva'                     => 0,
            'total'                   => 0,
        ]);

        $routeName = Route::has('admin.licitacion-propuestas.show')
            ? 'admin.licitacion-propuestas.show'
            : (Route::has('licitacion-propuestas.show') ? 'licitacion-propuestas.show' : null);

        if ($routeName) {
            return redirect()
                ->route($routeName, ['licitacionPropuesta' => $propuesta->id])
                ->with('status', 'Propuesta creada. Procesa cada requisición con IA.');
        }

        return back()->with('status', 'Propuesta creada.');
    }

    public function show(LicitacionPropuesta $licitacionPropuesta)
    {
        // ✅ Cargar relaciones que existen
        $propuesta = $licitacionPropuesta->load([
            'items.requestItem.page',
            'items.product',
            // si agregas suggested_product_id + relación, aquí lo puedes activar:
            // 'items.suggestedProduct',
            'licitacionPdf',
        ]);

        $licitacionPdf = $propuesta->licitacionPdf;

        $splitsInfo = [];
        $allSplitsProcessed = false;

        if ($licitacionPdf) {
            $meta = $licitacionPdf->meta ?? [];
            $rawSplits = $meta['splits'] ?? [];
            if ($rawSplits instanceof Collection) $rawSplits = $rawSplits->toArray();
            if (!is_array($rawSplits)) $rawSplits = [];

            $processed = $propuesta->processed_split_indexes ?? [];
            if ($processed instanceof Collection) $processed = $processed->toArray();
            if (!is_array($processed)) $processed = [];

            foreach ($rawSplits as $idx => $split) {
                $from = $split['from'] ?? ($split['from_page'] ?? null);
                $to   = $split['to']   ?? ($split['to_page']   ?? null);

                $pages = $split['page_count'] ?? ($split['pages_count'] ?? null);
                if (!$pages && is_numeric($from) && is_numeric($to)) {
                    $pages = ((int)$to - (int)$from + 1);
                }

                $splitsInfo[] = [
                    'index' => $idx,
                    'from'  => $from,
                    'to'    => $to,
                    'pages' => $pages,
                    'state' => in_array($idx, $processed, true) ? 'done' : 'pending',
                ];
            }

            $allSplitsProcessed = !empty($splitsInfo)
                && collect($splitsInfo)->every(fn($s)=> ($s['state'] ?? null) === 'done');
        }

        return view('admin.licitacion_propuestas.show', [
            'propuesta'          => $propuesta,
            'licitacionPdf'      => $licitacionPdf,
            'splitsInfo'         => $splitsInfo,
            'allSplitsProcessed' => $allSplitsProcessed,
        ]);
    }

    public function processSplit(
        Request $request,
        LicitacionPropuesta $licitacionPropuesta,
        int $splitIndex,
        LicitacionIaService $iaService
    ) {
        $propuesta = $licitacionPropuesta;

        if (!$propuesta->licitacion_pdf_id || !$propuesta->licitacionPdf) {
            Log::warning('Intento de procesar split sin PDF asociado', [
                'propuesta_id' => $propuesta->id,
                'split_index'  => $splitIndex,
            ]);
            return back()->with('error', 'Esta propuesta no tiene PDF asociado.');
        }

        try {
            // ✅ si reprocesas: borra items previos de ese split (opcional)
            // (si NO quieres duplicados cuando “Reprocesar”, descomenta esto)
            // $propuesta->items()->whereNull('licitacion_request_item_id')->delete();

            $iaService->processSplitWithAi($propuesta, $splitIndex);

            $processed = $propuesta->processed_split_indexes ?? [];
            if ($processed instanceof Collection) $processed = $processed->toArray();
            if (!is_array($processed)) $processed = [];

            if (!in_array($splitIndex, $processed, true)) {
                $processed[] = $splitIndex;
            }

            $propuesta->processed_split_indexes = $processed;
            $propuesta->save();

            // ✅ matching automático (sugerencias)
            MatchLicitacionPropuestaItems::dispatch($propuesta->id);

            return redirect()
                ->route('admin.licitacion-propuestas.show', ['licitacionPropuesta' => $propuesta->id])
                ->with('status', "Split {$splitIndex} procesado. Matching en cola.");
        } catch (\Throwable $e) {
            Log::error('Error al procesar split de licitación con IA', [
                'propuesta_id' => $propuesta->id,
                'split_index'  => $splitIndex,
                'error'        => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * ✅ APLICAR coincidencia (tú decides "sí aplica")
     * POST admin/licitacion-propuestas/items/{item}/apply
     */
    public function applyMatch(Request $request, LicitacionPropuestaItem $item)
    {
        $data = $request->validate([
            'product_id'      => ['required','integer'],
            'precio_unitario' => ['nullable','numeric','min:0'],
        ]);

        $item->product_id = (int) $data['product_id'];

        if (array_key_exists('precio_unitario', $data) && $data['precio_unitario'] !== null) {
            $item->precio_unitario = (float) $data['precio_unitario'];
        } else {
            $item->loadMissing('product');
            $item->precio_unitario = (float) ($item->product->price ?? 0);
        }

        $qty = (float) ($item->cantidad_propuesta ?? 0);
        $pu  = (float) ($item->precio_unitario ?? 0);
        $item->subtotal = $qty * $pu;

        if (property_exists($item, 'manual_selected') || isset($item->manual_selected)) {
            $item->manual_selected = true;
        }
        if (property_exists($item, 'match_status') || isset($item->match_status)) {
            $item->match_status = 'applied';
        }

        if (empty($item->motivo_seleccion)) {
            $item->motivo_seleccion = 'Seleccionado manualmente';
        }

        $item->save();

        $this->recalcTotals($item->propuesta);

        return back()->with('status', 'Coincidencia aplicada.');
    }

    /**
     * ❌ NO APLICA (rechazar sugerencia)
     * POST admin/licitacion-propuestas/items/{item}/reject
     */
    public function rejectMatch(Request $request, LicitacionPropuestaItem $item)
    {
        $item->product_id = null;
        $item->match_score = null;

        if (property_exists($item, 'manual_selected') || isset($item->manual_selected)) {
            $item->manual_selected = true;
        }
        if (property_exists($item, 'match_status') || isset($item->match_status)) {
            $item->match_status = 'rejected';
        }

        if (empty($item->motivo_seleccion)) {
            $item->motivo_seleccion = 'No aplica / rechazado';
        }

        $item->precio_unitario = null;
        $item->subtotal = 0;

        $item->save();

        $this->recalcTotals($item->propuesta);

        return back()->with('status', 'Marcado como NO aplica.');
    }

    public function mergeGlobal(Request $request, LicitacionPropuesta $licitacionPropuesta)
    {
        $propuesta = $licitacionPropuesta;

        $pdf = $propuesta->licitacionPdf;
        $splits = $pdf ? (($pdf->meta ?? [])['splits'] ?? []) : [];
        if ($splits instanceof Collection) $splits = $splits->toArray();
        if (!is_array($splits)) $splits = [];

        $processed = $propuesta->processed_split_indexes ?? [];
        if ($processed instanceof Collection) $processed = $processed->toArray();
        if (!is_array($processed)) $processed = [];

        if (count($splits) === 0) {
            return back()->with('error', 'No hay splits configurados.');
        }
        if (count($processed) < count($splits)) {
            return back()->with('error', 'Faltan splits por procesar.');
        }

        $this->recalcTotals($propuesta);
        $propuesta->merge_status = 'merged';
        $propuesta->save();

        return back()->with('status', 'Merge global realizado. Totales actualizados.');
    }

    public function edit(LicitacionPropuesta $licitacionPropuesta)
    {
        $licitacionPropuesta->load(['items.requestItem.page','items.product']);

        return view('admin.licitacion_propuestas.edit', [
            'propuesta' => $licitacionPropuesta
        ]);
    }

    public function update(Request $request, LicitacionPropuesta $licitacionPropuesta)
    {
        $data = $request->validate([
            'titulo' => ['nullable', 'string', 'max:255'],
            'moneda' => ['nullable', 'string', 'max:10'],
            'fecha'  => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $licitacionPropuesta->update($data);

        return back()->with('status', 'Propuesta actualizada.');
    }

    public function destroy(LicitacionPropuesta $licitacionPropuesta)
    {
        $licitacionPropuesta->delete();

        return back()->with('status', 'Propuesta eliminada.');
    }

    protected function recalcTotals(LicitacionPropuesta $propuesta): void
    {
        $subtotal = (float) $propuesta->items()->sum('subtotal');
        $iva      = $subtotal * 0.16;
        $total    = $subtotal + $iva;

        $propuesta->subtotal = $subtotal;
        $propuesta->iva      = $iva;
        $propuesta->total    = $total;
        $propuesta->save();
    }

    protected function generarCodigo(): string
    {
        $nextId = (LicitacionPropuesta::max('id') ?? 0) + 1;

        return 'PRO-' . now()->format('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
