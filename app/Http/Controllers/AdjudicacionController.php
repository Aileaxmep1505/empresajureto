<?php

namespace App\Http\Controllers;

use App\Models\Adjudicacion;
use App\Models\AdjudicacionItem;
use App\Models\PropuestaComercial;
use App\Models\PropuestaComercialItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdjudicacionController extends Controller
{
    /** Pantalla intermedia: marcar ganada / perdida por partida. */
    public function create(PropuestaComercial $propuestaComercial)
    {
        $propuestaComercial->loadMissing([
            'items' => fn ($q) => $q->orderBy('sort')->orderBy('id'),
        ]);

        $items = $propuestaComercial->items->values()->map(function ($item, $index) {
            $qty = (float) ($item->cantidad_cotizada ?: $item->cantidad_maxima ?: $item->cantidad_minima ?: 1);
            $price = (float) $item->precio_unitario;

            return [
                'id' => $item->id,
                'numero' => $item->partida_numero ?: ($index + 1),
                'descripcion' => $item->descripcion_original,
                'unidad' => $item->unidad_solicitada ?: 'pz',
                'cantidad' => $qty,
                'costo_unitario' => (float) $item->costo_unitario,
                'precio_unitario' => $price,
                'subtotal' => (float) ($item->subtotal ?: $price * $qty),
            ];
        });

        $folio = $propuestaComercial->folio
            ?: ('ADJ-' . str_pad((string) $propuestaComercial->id, 6, '0', STR_PAD_LEFT));

        return view('propuestas_comerciales.adjudicacion', [
            'propuestaComercial' => $propuestaComercial,
            'items' => $items,
            'folio' => $folio,
        ]);
    }

    /** Guarda la adjudicación: ganadas = venta, perdidas = antecedente. */
    public function store(Request $request, PropuestaComercial $propuestaComercial)
    {
        $data = $request->validate([
            'partidas' => ['required', 'array', 'min:1'],
            'partidas.*.item_id' => ['required', 'integer'],
            'partidas.*.resultado' => ['required', 'in:ganada,perdida'],
            'partidas.*.proveedor_ganador' => ['nullable', 'string', 'max:255'],
            'partidas.*.precio_ganador' => ['nullable', 'numeric', 'min:0'],
            'partidas.*.precio_ofertado' => ['nullable', 'numeric', 'min:0'],
            'partidas.*.motivo_perdida' => ['nullable', 'string'],
            'partidas.*.analisis_ia' => ['nullable', 'string'],
        ]);

        $propuestaComercial->loadMissing('items');
        $itemsById = $propuestaComercial->items->keyBy('id');

        $adjudicacion = DB::transaction(function () use ($data, $propuestaComercial, $itemsById) {
            $adj = Adjudicacion::create([
                'propuesta_comercial_id' => $propuestaComercial->id,
                'folio' => $propuestaComercial->folio
                    ?: ('ADJ-' . str_pad((string) $propuestaComercial->id, 6, '0', STR_PAD_LEFT)),
                'titulo' => $propuestaComercial->titulo,
                'cliente' => $propuestaComercial->cliente,
                'status' => 'generada',
                'meta' => ['origen' => 'cliente_show'],
            ]);

            $sort = 0;
            $ganadas = 0;
            $perdidas = 0;
            $subtotalGanadas = 0;

            foreach ($data['partidas'] as $row) {
                $item = $itemsById->get($row['item_id']);
                if (!$item) {
                    continue;
                }

                $sort++;
                $resultado = $row['resultado'];

                $qty = (float) ($item->cantidad_cotizada ?: $item->cantidad_maxima ?: $item->cantidad_minima ?: 1);
                $precioOfertado = isset($row['precio_ofertado'])
                    ? (float) $row['precio_ofertado']
                    : (float) $item->precio_unitario;
                $subtotal = round($precioOfertado * $qty, 2);

                $precioGanador = isset($row['precio_ganador']) && $row['precio_ganador'] !== null
                    ? (float) $row['precio_ganador']
                    : null;

                $difMonto = null;
                $difPct = null;
                if ($resultado === 'perdida' && $precioGanador !== null && $precioGanador > 0) {
                    $difMonto = round($precioOfertado - $precioGanador, 2);
                    $difPct = round((($precioOfertado - $precioGanador) / $precioGanador) * 100, 2);
                }

                AdjudicacionItem::create([
                    'adjudicacion_id' => $adj->id,
                    'propuesta_comercial_item_id' => $item->id,
                    'sort' => $sort,
                    'partida_numero' => $item->partida_numero ?: $sort,
                    'descripcion_original' => $item->descripcion_original,
                    'unidad_solicitada' => $item->unidad_solicitada,
                    'cantidad' => $qty,
                    'costo_unitario' => (float) $item->costo_unitario,
                    'precio_unitario' => (float) $item->precio_unitario,
                    'subtotal' => $resultado === 'ganada' ? $subtotal : 0,
                    'resultado' => $resultado,
                    'motivo_perdida' => $resultado === 'perdida' ? ($row['motivo_perdida'] ?? null) : null,
                    'proveedor_ganador' => $resultado === 'perdida' ? ($row['proveedor_ganador'] ?? null) : null,
                    'precio_ganador' => $resultado === 'perdida' ? $precioGanador : null,
                    'precio_ofertado' => $precioOfertado,
                    'diferencia_monto' => $difMonto,
                    'diferencia_pct' => $difPct,
                    'analisis_ia' => $resultado === 'perdida' ? ($row['analisis_ia'] ?? null) : null,
                ]);

                if ($resultado === 'ganada') {
                    $ganadas++;
                    $subtotalGanadas += $subtotal;
                } else {
                    $perdidas++;
                }
            }

            $impuestoPct = (float) ($propuestaComercial->porcentaje_impuesto ?: 16);

            $adj->update([
                'total_partidas' => $ganadas + $perdidas,
                'ganadas_count' => $ganadas,
                'perdidas_count' => $perdidas,
                'subtotal_ganadas' => round($subtotalGanadas, 2),
                'total_ganadas' => round($subtotalGanadas * (1 + $impuestoPct / 100), 2),
            ]);

            return $adj;
        });

        return redirect()
            ->route('adjudicaciones.show', $adjudicacion)
            ->with('status', 'Adjudicación generada. Las partidas perdidas quedaron guardadas como antecedente.');
    }

    /** Resultado: la venta (ganadas) + el antecedente (perdidas). */
    public function show(Adjudicacion $adjudicacion)
    {
        $adjudicacion->loadMissing(['propuesta', 'ganadas', 'perdidas']);

        return view('propuestas_comerciales.adjudicacion_show', compact('adjudicacion'));
    }

    /** AJAX: calcula diferencia y arma el análisis de una partida perdida. */
    public function analizarPerdida(Request $request, PropuestaComercial $propuestaComercial)
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer'],
            'proveedor_ganador' => ['nullable', 'string', 'max:255'],
            'precio_ganador' => ['nullable', 'numeric', 'min:0'],
            'precio_ofertado' => ['nullable', 'numeric', 'min:0'],
            'motivo_perdida' => ['nullable', 'string'],
        ]);

        $item = PropuestaComercialItem::where('propuesta_comercial_id', $propuestaComercial->id)
            ->findOrFail($data['item_id']);

        $precioOfertado = (float) ($data['precio_ofertado'] ?? $item->precio_unitario);
        $precioGanador = (float) ($data['precio_ganador'] ?? 0);

        $difMonto = $precioGanador > 0 ? round($precioOfertado - $precioGanador, 2) : null;
        $difPct = $precioGanador > 0 ? round((($precioOfertado - $precioGanador) / $precioGanador) * 100, 2) : null;

        $analisis = $this->generarAnalisisPerdida(
            $item->descripcion_original,
            $precioOfertado,
            $precioGanador,
            $data['proveedor_ganador'] ?? null,
            $data['motivo_perdida'] ?? null,
            $difMonto,
            $difPct
        );

        return response()->json([
            'ok' => true,
            'diferencia_monto' => $difMonto,
            'diferencia_pct' => $difPct,
            'analisis_ia' => $analisis,
        ]);
    }

    /**
     * Arma el texto del antecedente.
     *
     * NOTA: aquí va la IA real. Si me pasas el servicio/cliente que usa tu
     * endpoint de "clarificationSuggest", reemplazo este cuerpo para que la
     * redacción la genere el modelo con un prompt. Por ahora produce un
     * análisis determinístico con los números.
     */
    private function generarAnalisisPerdida(
        ?string $descripcion,
        float $precioOfertado,
        float $precioGanador,
        ?string $proveedorGanador,
        ?string $motivo,
        ?float $difMonto,
        ?float $difPct
    ): string {
        $partes = [];
        $partes[] = 'Partida: ' . trim((string) $descripcion);

        if ($precioGanador > 0) {
            $partes[] = sprintf(
                'Ofertamos %s y el precio ganador fue %s%s.',
                '$' . number_format($precioOfertado, 2),
                '$' . number_format($precioGanador, 2),
                $proveedorGanador ? ' (' . $proveedorGanador . ')' : ''
            );

            if ($difMonto !== null) {
                if ($difMonto > 0) {
                    $partes[] = sprintf(
                        'Quedamos %s arriba (%s%% más caros).',
                        '$' . number_format($difMonto, 2),
                        number_format(abs($difPct), 2)
                    );
                } elseif ($difMonto < 0) {
                    $partes[] = sprintf(
                        'Estuvimos %s por debajo del ganador (%s%% más baratos); la pérdida no fue por precio.',
                        '$' . number_format(abs($difMonto), 2),
                        number_format(abs($difPct), 2)
                    );
                } else {
                    $partes[] = 'Igualamos el precio ganador; el factor decisivo no fue económico.';
                }
            }
        } else {
            $partes[] = 'No se capturó el precio ganador, por lo que el análisis es cualitativo.';
        }

        if ($motivo) {
            $partes[] = 'Motivo registrado: ' . trim($motivo);
        }

        return implode(' ', $partes);
    }
}