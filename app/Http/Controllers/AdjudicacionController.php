<?php

namespace App\Http\Controllers;

use App\Models\PropuestaComercial;
use App\Models\PropuestaComercialItem;
use App\Models\PropuestaResultado;
use App\Models\PropuestaResultadoItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class AdjudicacionController extends Controller
{
    /** Pantalla intermedia: marcar ganada / perdida por partida (autoguardado). */
    public function create(PropuestaComercial $propuestaComercial)
    {
        $propuestaComercial->loadMissing([
            'items' => fn ($q) => $q->orderBy('sort')->orderBy('id'),
        ]);

        $resultado = PropuestaResultado::where('propuesta_comercial_id', $propuestaComercial->id)
            ->latest('id')
            ->first();

        $guardadas = $resultado
            ? $resultado->items()->get()->keyBy('propuesta_comercial_item_id')
            : collect();

        $items = $propuestaComercial->items->values()->map(function ($item, $index) use ($guardadas) {
            $qty = (float) ($item->cantidad_cotizada ?: $item->cantidad_maxima ?: $item->cantidad_minima ?: 1);
            $price = (float) $item->precio_unitario;
            $saved = $guardadas->get($item->id);

            return [
                'id' => $item->id,
                'numero' => $item->partida_numero ?: ($index + 1),
                'descripcion' => $item->descripcion_original,
                'unidad' => $item->unidad_solicitada ?: 'pz',
                'cantidad' => $qty,
                'costo_unitario' => (float) $item->costo_unitario,
                'precio_unitario' => $price,
                'subtotal' => (float) ($item->subtotal ?: $price * $qty),
                'saved' => $saved ? [
                    'resultado' => $saved->resultado,
                    'proveedor_ganador' => $saved->proveedor_ganador,
                    'precio_ganador' => $saved->precio_ganador !== null ? (float) $saved->precio_ganador : '',
                    'motivo_perdida' => $saved->motivo_perdida,
                    'analisis_ia' => $saved->analisis_ia,
                ] : null,
            ];
        });

        $folio = $propuestaComercial->folio
            ?: ('ADJ-' . str_pad((string) $propuestaComercial->id, 6, '0', STR_PAD_LEFT));

        return view('propuestas_comerciales.adjudicacion', [
            'propuestaComercial' => $propuestaComercial,
            'items' => $items,
            'folio' => $folio,
            'adjudicacionId' => optional($resultado)->id,
        ]);
    }

    /** AJAX: guarda UNA partida (ganada/perdida) de forma individual. */
    public function guardarPartida(Request $request, PropuestaComercial $propuestaComercial)
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer'],
            'resultado' => ['required', 'in:ganada,perdida'],
            'proveedor_ganador' => ['nullable', 'string', 'max:255'],
            'precio_ganador' => ['nullable', 'numeric', 'min:0'],
            'precio_ofertado' => ['nullable', 'numeric', 'min:0'],
            'motivo_perdida' => ['nullable', 'string'],
            'analisis_ia' => ['nullable', 'string'],
        ]);

        $item = PropuestaComercialItem::where('propuesta_comercial_id', $propuestaComercial->id)
            ->findOrFail($data['item_id']);

        try {
            $resultado = DB::transaction(function () use ($data, $propuestaComercial, $item) {
                $res = $this->currentResultado($propuestaComercial);

                $resultadoTipo = $data['resultado'];
                $qty = (float) ($item->cantidad_cotizada ?: $item->cantidad_maxima ?: $item->cantidad_minima ?: 1);
                $precioOfertado = isset($data['precio_ofertado']) && $data['precio_ofertado'] !== null && $data['precio_ofertado'] !== ''
                    ? (float) $data['precio_ofertado']
                    : (float) $item->precio_unitario;
                $subtotal = round($precioOfertado * $qty, 2);

                $precioGanador = isset($data['precio_ganador']) && $data['precio_ganador'] !== null && $data['precio_ganador'] !== ''
                    ? (float) $data['precio_ganador']
                    : null;

                $difMonto = null;
                $difPct = null;
                if ($resultadoTipo === 'perdida' && $precioGanador !== null && $precioGanador > 0) {
                    $difMonto = round($precioOfertado - $precioGanador, 2);
                    $difPct = round((($precioOfertado - $precioGanador) / $precioGanador) * 100, 2);
                }

                PropuestaResultadoItem::updateOrCreate(
                    [
                        'propuesta_resultado_id' => $res->id,
                        'propuesta_comercial_item_id' => $item->id,
                    ],
                    [
                        'sort' => $item->sort ?: 0,
                        'partida_numero' => $item->partida_numero ?: ($item->sort ?: 0),
                        'descripcion_original' => $item->descripcion_original,
                        'unidad_solicitada' => $item->unidad_solicitada,
                        'cantidad' => $qty,
                        'costo_unitario' => (float) $item->costo_unitario,
                        'precio_unitario' => (float) $item->precio_unitario,
                        'precio_ofertado' => $precioOfertado,
                        'subtotal' => $resultadoTipo === 'ganada' ? $subtotal : 0,
                        'resultado' => $resultadoTipo,
                        'motivo_perdida' => $resultadoTipo === 'perdida' ? ($data['motivo_perdida'] ?? null) : null,
                        'proveedor_ganador' => $resultadoTipo === 'perdida' ? ($data['proveedor_ganador'] ?? null) : null,
                        'precio_ganador' => $resultadoTipo === 'perdida' ? $precioGanador : null,
                        'diferencia_monto' => $difMonto,
                        'diferencia_pct' => $difPct,
                        'analisis_ia' => $resultadoTipo === 'perdida' ? ($data['analisis_ia'] ?? null) : null,
                    ]
                );

                $this->recalcResultado($res, $propuestaComercial);

                return $res->fresh();
            });
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $items = $resultado->items()->get();

        return response()->json([
            'ok' => true,
            'adjudicacion_id' => $resultado->id,
            'counters' => [
                'ganadas' => $items->where('resultado', 'ganada')->count(),
                'perdidas' => $items->where('resultado', 'perdida')->count(),
                'subtotal_ganadas' => round((float) $items->where('resultado', 'ganada')->sum('subtotal'), 2),
            ],
        ]);
    }

    /** Obtiene (o crea) el resultado de este flujo para la propuesta. */
    private function currentResultado(PropuestaComercial $propuestaComercial): PropuestaResultado
    {
        $res = PropuestaResultado::where('propuesta_comercial_id', $propuestaComercial->id)
            ->latest('id')
            ->first();

        if ($res) {
            return $res;
        }

        return PropuestaResultado::create([
            'propuesta_comercial_id' => $propuestaComercial->id,
            'folio' => $propuestaComercial->folio
                ?: ('ADJ-' . str_pad((string) $propuestaComercial->id, 6, '0', STR_PAD_LEFT)),
            'titulo' => $propuestaComercial->titulo,
            'cliente' => $propuestaComercial->cliente,
            'status' => 'generada',
            'meta' => ['origen' => 'analisis_partidas'],
        ]);
    }

    /** Recalcula los contadores de la cabecera. */
    private function recalcResultado(PropuestaResultado $res, PropuestaComercial $propuestaComercial): void
    {
        $items = $res->items()->get();
        $ganadas = $items->where('resultado', 'ganada')->count();
        $perdidas = $items->where('resultado', 'perdida')->count();
        $subtotalGanadas = (float) $items->where('resultado', 'ganada')->sum('subtotal');
        $impuestoPct = (float) ($propuestaComercial->porcentaje_impuesto ?: 16);

        $res->update([
            'total_partidas' => $ganadas + $perdidas,
            'ganadas_count' => $ganadas,
            'perdidas_count' => $perdidas,
            'subtotal_ganadas' => round($subtotalGanadas, 2),
            'total_ganadas' => round($subtotalGanadas * (1 + $impuestoPct / 100), 2),
        ]);
    }

    /** Deriva ganadas/perdidas: ganada = TODO lo que no se marcó perdida. */
    private function derivarResultado(PropuestaResultado $resultado): array
    {
        $resultado->loadMissing(['propuesta.items', 'items']);

        $savedByItem = $resultado->items->keyBy('propuesta_comercial_item_id');
        $propuestaItems = optional($resultado->propuesta)->items
            ? $resultado->propuesta->items->sortBy('sort')->values()
            : collect();

        $ganadas = [];
        $perdidas = [];
        $subtotalGanadas = 0;

        foreach ($propuestaItems as $index => $item) {
            $saved = $savedByItem->get($item->id);
            $qty = (float) ($item->cantidad_cotizada ?: $item->cantidad_maxima ?: $item->cantidad_minima ?: 1);
            $numero = $item->partida_numero ?: ($index + 1);

            if ($saved && $saved->resultado === 'perdida') {
                $perdidas[] = [
                    'num' => $numero,
                    'desc' => $item->descripcion_original,
                    'unit' => $item->unidad_solicitada ?: 'pz',
                    'qty' => $qty,
                    'offered' => (float) ($saved->precio_ofertado ?: $item->precio_unitario),
                    'ganador' => $saved->precio_ganador !== null ? (float) $saved->precio_ganador : null,
                    'proveedor' => $saved->proveedor_ganador,
                    'motivo' => $saved->motivo_perdida,
                    'analisis' => $saved->analisis_ia,
                    'dif' => $saved->diferencia_monto !== null ? (float) $saved->diferencia_monto : null,
                    'difPct' => $saved->diferencia_pct !== null ? (float) $saved->diferencia_pct : null,
                ];
                continue;
            }

            $precioOfertado = (float) ((optional($saved)->precio_ofertado) ?: $item->precio_unitario);
            $subtotal = round($precioOfertado * $qty, 2);
            $subtotalGanadas += $subtotal;

            $ganadas[] = [
                'num' => $numero,
                'desc' => $item->descripcion_original,
                'unit' => $item->unidad_solicitada ?: 'pz',
                'qty' => $qty,
                'offered' => $precioOfertado,
                'subtotal' => $subtotal,
            ];
        }

        $impuestoPct = (float) (optional($resultado->propuesta)->porcentaje_impuesto ?: 16);

        return [
            'ganadas' => $ganadas,
            'perdidas' => $perdidas,
            'subtotalGanadas' => round($subtotalGanadas, 2),
            'totalGanadas' => round($subtotalGanadas * (1 + $impuestoPct / 100), 2),
            'impuestoPct' => $impuestoPct,
        ];
    }

    /** Resultado: ganadas = TODO lo que no se marcó perdida; perdidas = lo guardado. */
    public function show(PropuestaResultado $resultado)
    {
        $d = $this->derivarResultado($resultado);

        return view('propuestas_comerciales.adjudicacion_show', [
            'adjudicacion' => $resultado,
            'ganadas' => $d['ganadas'],
            'perdidas' => $d['perdidas'],
            'subtotalGanadas' => $d['subtotalGanadas'],
            'totalGanadas' => $d['totalGanadas'],
        ]);
    }

    /** PDF de remisión (nota de entrega) con las partidas ganadas. */
    public function remisionPdf(PropuestaResultado $resultado)
    {
        $d = $this->derivarResultado($resultado);
        $iva = round($d['subtotalGanadas'] * ($d['impuestoPct'] / 100), 2);
        $folio = 'REM-' . str_pad((string) $resultado->id, 6, '0', STR_PAD_LEFT);

        $pdf = Pdf::loadView('propuestas_comerciales.remision_pdf', [
            'resultado' => $resultado,
            'folio' => $folio,
            'ganadas' => $d['ganadas'],
            'subtotal' => $d['subtotalGanadas'],
            'iva' => $iva,
            'ivaPct' => $d['impuestoPct'],
            'total' => $d['totalGanadas'],
            'generadoEn' => now()->format('d/m/Y'),
        ])->setPaper('letter', 'portrait');

        return $pdf->download('remision_' . $folio . '.pdf');
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

    /** PDF formal del análisis (se arma desde lo capturado en la pantalla intermedia). */
    public function analisisPdf(Request $request, PropuestaComercial $propuestaComercial)
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

        $ganadas = [];
        $perdidas = [];
        $subtotalGanadas = 0;
        $montoPerdido = 0;
        $sort = 0;

        foreach ($data['partidas'] as $row) {
            $item = $itemsById->get($row['item_id']);
            if (!$item) {
                continue;
            }
            $sort++;

            $qty = (float) ($item->cantidad_cotizada ?: $item->cantidad_maxima ?: $item->cantidad_minima ?: 1);
            $offered = isset($row['precio_ofertado']) ? (float) $row['precio_ofertado'] : (float) $item->precio_unitario;
            $subtotal = round($offered * $qty, 2);

            $reg = [
                'num' => $item->partida_numero ?: $sort,
                'desc' => $item->descripcion_original ?: 'Producto sin descripción',
                'unit' => $item->unidad_solicitada ?: 'pz',
                'qty' => $qty,
                'offered' => $offered,
                'subtotal' => $subtotal,
            ];

            if (($row['resultado'] ?? 'ganada') === 'perdida') {
                $ganador = isset($row['precio_ganador']) && $row['precio_ganador'] !== null && $row['precio_ganador'] !== '' ? (float) $row['precio_ganador'] : 0;
                $reg['ganador'] = $ganador;
                $reg['proveedor'] = trim((string) ($row['proveedor_ganador'] ?? ''));
                $reg['motivo'] = trim((string) ($row['motivo_perdida'] ?? ''));
                $reg['analisis'] = trim((string) ($row['analisis_ia'] ?? ''));
                $reg['dif'] = $ganador > 0 ? round($offered - $ganador, 2) : null;
                $reg['difPct'] = $ganador > 0 ? round((($offered - $ganador) / $ganador) * 100, 2) : null;
                $montoPerdido += $subtotal;
                $perdidas[] = $reg;
            } else {
                $subtotalGanadas += $subtotal;
                $ganadas[] = $reg;
            }
        }

        $total = count($ganadas) + count($perdidas);
        $tasaExito = $total > 0 ? round(count($ganadas) / $total * 100, 2) : 0;

        $porPrecio = array_values(array_filter($perdidas, fn ($p) => ($p['ganador'] ?? 0) > 0 && ($p['dif'] ?? 0) > 0));
        $noPrecio = array_values(array_filter($perdidas, fn ($p) => ($p['ganador'] ?? 0) > 0 && ($p['dif'] ?? 0) <= 0));
        $sinDato = array_values(array_filter($perdidas, fn ($p) => !(($p['ganador'] ?? 0) > 0)));

        $promArriba = count($porPrecio) ? array_sum(array_column($porPrecio, 'difPct')) / count($porPrecio) : 0;
        usort($porPrecio, fn ($a, $b) => $b['difPct'] <=> $a['difPct']);
        $mayorBrecha = $porPrecio[0] ?? null;

        $resumen = [
            'ganadas' => count($ganadas),
            'perdidas' => count($perdidas),
            'total' => $total,
            'tasaExito' => $tasaExito,
            'subtotalGanadas' => round($subtotalGanadas, 2),
            'montoPerdido' => round($montoPerdido, 2),
            'porPrecio' => count($porPrecio),
            'noPrecio' => count($noPrecio),
            'sinDato' => count($sinDato),
            'promArriba' => round($promArriba, 2),
            'mayorBrecha' => $mayorBrecha,
        ];

        $folio = $propuestaComercial->folio
            ?: ('ADJ-' . str_pad((string) $propuestaComercial->id, 6, '0', STR_PAD_LEFT));

        $pdf = Pdf::loadView('propuestas_comerciales.adjudicacion_pdf', [
            'propuestaComercial' => $propuestaComercial,
            'folio' => $folio,
            'ganadas' => $ganadas,
            'perdidas' => $perdidas,
            'resumen' => $resumen,
            'diagnostico' => $this->construirDiagnostico($resumen),
            'recomendaciones' => $this->construirRecomendaciones($resumen),
            'generadoEn' => now()->format('d/m/Y H:i'),
        ])->setPaper('letter', 'portrait');

        return $pdf->download('analisis_' . preg_replace('/[^\w\-]+/', '_', $folio) . '.pdf');
    }

    private function construirDiagnostico(array $r): string
    {
        if ($r['perdidas'] === 0) {
            return 'Se marcaron todas las partidas como ganadas. Excelente resultado: no hay pérdidas que analizar en esta licitación.';
        }

        $p = [];
        $p[] = "De {$r['total']} partidas participadas se ganaron {$r['ganadas']} y se perdieron {$r['perdidas']} (tasa de éxito {$r['tasaExito']}%).";

        if ($r['porPrecio'] > 0) {
            $p[] = "El principal factor de pérdida fue el PRECIO: {$r['porPrecio']} partida(s) quedaron por arriba del licitante ganador, en promedio {$r['promArriba']}% más caras.";
            if ($r['mayorBrecha']) {
                $mb = $r['mayorBrecha'];
                $p[] = 'La mayor brecha fue en "' . $mb['desc'] . '", donde ofertamos $' . number_format($mb['offered'], 2)
                    . ' contra $' . number_format($mb['ganador'], 2) . ' del ganador (' . number_format($mb['difPct'], 2) . '% arriba).';
            }
        }
        if ($r['noPrecio'] > 0) {
            $p[] = "En {$r['noPrecio']} partida(s) igualamos o mejoramos el precio del ganador y aun así no se ganaron: la causa no fue económica (revisar técnico, muestras, tiempos o documentación).";
        }
        if ($r['sinDato'] > 0) {
            $p[] = "En {$r['sinDato']} partida(s) no se capturó el precio del ganador, por lo que falta inteligencia de la competencia para esos casos.";
        }

        return implode(' ', $p);
    }

    private function construirRecomendaciones(array $r): array
    {
        $recos = [];

        if ($r['porPrecio'] > 0) {
            $obj = '';
            if ($r['mayorBrecha']) {
                $mb = $r['mayorBrecha'];
                $obj = ' Por ejemplo, para "' . $mb['desc'] . '" había que bajar a ~$' . number_format($mb['ganador'], 2) . ' o menos.';
            }
            $recos[] = "Ajustar precio en las {$r['porPrecio']} partida(s) perdidas por costo: renegociar con el proveedor, buscar otra fuente de surtido o reducir el margen." . $obj;
            $recos[] = 'Pedir cotizaciones de varios proveedores antes de ofertar para tener el costo más bajo posible y competir en precio.';
        }
        if ($r['noPrecio'] > 0) {
            $recos[] = "Revisar el cumplimiento técnico en las {$r['noPrecio']} partida(s) donde el precio sí era competitivo: validar ficha técnica, marca, muestras y tiempos de entrega.";
        }
        if ($r['sinDato'] > 0) {
            $recos[] = "Conseguir el acta de fallo para registrar el precio y nombre del ganador en las {$r['sinDato']} partida(s) sin dato, y afinar próximas ofertas.";
        }

        $recos[] = 'Construir una base de datos de licitantes ganadores y sus precios por partida, para usarla como referencia en futuras participaciones.';
        $recos[] = 'Guardar este análisis como antecedente y compararlo contra próximas licitaciones del mismo cliente para detectar el rango de precios con el que se gana.';

        return $recos;
    }
}