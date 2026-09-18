<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\PickWave;
use App\Models\WmsCrossdockAssignment;
use App\Models\WmsReceptionLine;
use App\Services\WmsDemandService;
use App\Services\WmsStockService;
use Illuminate\Http\Request;

/**
 * Cross-docking.
 *
 * Cruza lo recibido en los últimos días contra lo que siguen pidiendo las
 * olas de picking abiertas. Lo que coincide se puede mandar directo al andén
 * del pedido en vez de guardarse en rack.
 */
class WmsCrossdockController extends Controller
{
    private const DIAS = 14;

    public function index(WmsDemandService $demanda)
    {
        return view('admin.wms.ops.crossdock', [
            'oportunidades' => $this->oportunidades($demanda),
            'asignaciones' => WmsCrossdockAssignment::with(['item', 'wave', 'reception', 'stagingLocation', 'creator'])
                ->latest('id')->limit(40)->get(),
            'ubicaciones' => Location::orderBy('code')->get(['id', 'warehouse_id', 'code']),
            'dias' => self::DIAS,
        ]);
    }

    public function assign(Request $request, WmsDemandService $demanda, WmsStockService $stock)
    {
        $data = $request->validate([
            'reception_line_id' => ['required', 'exists:wms_reception_lines,id'],
            'pick_wave_id' => ['required', 'exists:pick_waves,id'],
            'wave_line_id' => ['nullable', 'string', 'max:80'],
            'qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'staging_location_id' => ['nullable', 'exists:locations,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $linea = WmsReceptionLine::findOrFail($data['reception_line_id']);

        // Se revalida contra el cálculo vigente: ni más de lo recibido, ni más de lo pedido.
        $op = collect($this->oportunidades($demanda))->first(fn ($o) => $o['linea']->id === $linea->id
            && $o['ola']['wave_id'] === (int) $data['pick_wave_id']
            && (string) ($o['ola']['line_id'] ?? '') === (string) ($data['wave_line_id'] ?? ''));

        if (! $op) {
            return back()->with('error', 'Esa oportunidad ya no está disponible (se asignó o la ola ya se surtió).');
        }

        $qty = min((int) $data['qty'], $op['sugerido']);

        $aviso = '';

        // Si se eligió un andén distinto a donde se guardó lo recibido, se mueve el stock.
        if (! empty($data['staging_location_id']) && $linea->location_id && (int) $data['staging_location_id'] !== (int) $linea->location_id) {
            try {
                $stock->transfer(
                    $linea->catalog_item_id, $linea->location_id, (int) $data['staging_location_id'], $qty,
                    $request->user()->id, 'crossdock', 'Cross-docking a ' . $op['ola']['code'],
                    ['reception_line_id' => $linea->id, 'pick_wave_id' => $op['ola']['wave_id']]
                );
            } catch (\RuntimeException $e) {
                $aviso = ' Ojo: no se movió el stock al andén (' . $e->getMessage() . ').';
            }
        }

        WmsCrossdockAssignment::create([
            'reception_id' => $linea->reception_id,
            'reception_line_id' => $linea->id,
            'pick_wave_id' => $op['ola']['wave_id'],
            'wave_line_id' => $op['ola']['line_id'],
            'catalog_item_id' => $linea->catalog_item_id,
            'qty' => $qty,
            'staging_location_id' => $data['staging_location_id'] ?? null,
            'status' => 'asignado',
            'created_by' => $request->user()->id,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('ok', "Se asignaron {$qty} unidades a {$op['ola']['code']}." . $aviso);
    }

    /** Avanza la asignación: asignado -> en_anden -> entregado. */
    public function advance(Request $request, WmsCrossdockAssignment $assignment)
    {
        $siguiente = ['asignado' => 'en_anden', 'en_anden' => 'entregado'][$assignment->status] ?? null;

        if (! $siguiente) {
            return back()->with('error', 'Esa asignación ya no se puede avanzar.');
        }

        $assignment->update([
            'status' => $siguiente,
            'updated_by' => $request->user()->id,
            'staged_at' => $siguiente === 'en_anden' ? now() : $assignment->staged_at,
            'delivered_at' => $siguiente === 'entregado' ? now() : null,
        ]);

        return back()->with('ok', 'Asignación marcada como ' . mb_strtolower($assignment->status_label) . '.');
    }

    public function cancel(Request $request, WmsCrossdockAssignment $assignment)
    {
        if (in_array($assignment->status, ['asignado', 'en_anden'], true)) {
            $assignment->update(['status' => 'cancelado', 'updated_by' => $request->user()->id]);
        }

        return back()->with('ok', 'Asignación cancelada. La mercancía vuelve a estar disponible.');
    }

    // ===================== Cálculo =====================

    /**
     * @return array<int, array{linea: WmsReceptionLine, disponible: int, ola: array, faltante: int, sugerido: int}>
     */
    private function oportunidades(WmsDemandService $demanda): array
    {
        $pendiente = $demanda->pendientePorProducto();

        if ($pendiente->isEmpty()) {
            return [];
        }

        $lineas = WmsReceptionLine::with(['reception', 'catalogItem', 'location'])
            ->whereIn('catalog_item_id', $pendiente->keys())
            ->whereHas('reception', fn ($q) => $q->where('created_at', '>=', now()->subDays(self::DIAS)))
            ->latest('id')
            ->get();

        $activas = WmsCrossdockAssignment::whereIn('status', ['asignado', 'en_anden', 'entregado'])->get();
        $yaPorLinea = $activas->groupBy('reception_line_id')->map->sum('qty');
        // Lo entregado ya debería verse como surtido en la ola; solo descuenta lo que sigue en tránsito.
        $enTransito = $activas->whereIn('status', ['asignado', 'en_anden'])
            ->groupBy(fn ($a) => $a->pick_wave_id . '|' . $a->wave_line_id)->map->sum('qty');

        $salida = [];

        foreach ($lineas as $linea) {
            $disponible = (int) $linea->quantity - (int) ($yaPorLinea[$linea->id] ?? 0);

            foreach ($pendiente->get($linea->catalog_item_id, collect()) as $ola) {
                if ($disponible <= 0) {
                    break;
                }

                $faltante = $ola['pending'] - (int) ($enTransito[$ola['wave_id'] . '|' . $ola['line_id']] ?? 0);

                if ($faltante <= 0) {
                    continue;
                }

                $sugerido = min($disponible, $faltante);

                $salida[] = [
                    'linea' => $linea,
                    'disponible' => $disponible,
                    'ola' => $ola,
                    'faltante' => $faltante,
                    'sugerido' => $sugerido,
                ];

                $disponible -= $sugerido;
            }
        }

        return $salida;
    }
}
