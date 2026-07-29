<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tablero público de estatus de almacén (para pantalla en sala de espera).
 *
 * NO requiere login. Muestra SOLO información operativa (folios, estatus,
 * conteos, movimientos) — sin datos sensibles (precios, clientes, choferes).
 */
class WarehouseBoardController extends Controller
{
    /** Página del tablero (kiosco). */
    public function index()
    {
        return view('web.warehouse-board');
    }

    /** Datos en vivo (JSON) que la pantalla consulta cada pocos segundos. */
    public function data()
    {
        return response()->json([
            'ok'         => true,
            'receptions' => $this->receptions(),
            'picking'    => $this->picking(),
            'shipping'   => $this->shipping(),
            'movements'  => $this->movements(),
            'generated_at' => Carbon::now()->format('H:i:s'),
        ]);
    }

    protected function receptions(): array
    {
        $out = ['today' => 0, 'pending' => 0, 'recent' => []];
        try {
            if (!Schema::hasTable('wms_receptions')) {
                return $out;
            }

            $out['today'] = (int) DB::table('wms_receptions')->whereDate('created_at', today())->count();
            $out['pending'] = (int) DB::table('wms_receptions')->where('status', 'pendiente')->count();

            $out['recent'] = DB::table('wms_receptions')
                ->orderByDesc('id')
                ->limit(7)
                ->get(['id', 'folio', 'status', 'created_at'])
                ->map(fn ($r) => [
                    'title'  => (string) ($r->folio ?: ('REC-' . str_pad((string) $r->id, 5, '0', STR_PAD_LEFT))),
                    'status' => $this->receptionStatus((string) ($r->status ?? 'pendiente')),
                    'when'   => $this->ago($r->created_at),
                ])
                ->all();
        } catch (\Throwable $e) {
            // silencioso: el tablero nunca debe romperse.
        }

        return $out;
    }

    protected function picking(): array
    {
        $out = ['pending' => 0, 'in_progress' => 0, 'completed' => 0, 'recent' => []];
        try {
            if (!Schema::hasTable('pick_waves')) {
                return $out;
            }

            $rows = DB::table('pick_waves')->get(['id', 'task_number', 'code', 'status', 'updated_at', 'created_at']);

            foreach ($rows as $r) {
                $st = $this->pickStatus($r->status);
                if (isset($out[$st])) {
                    $out[$st]++;
                }
            }

            $out['recent'] = $rows
                ->sortByDesc(fn ($r) => $r->updated_at ?? $r->created_at)
                ->take(7)
                ->map(fn ($r) => [
                    'title'  => (string) ($r->task_number ?: $r->code ?: ('PICK-' . str_pad((string) $r->id, 3, '0', STR_PAD_LEFT))),
                    'status' => $this->pickStatus($r->status),
                    'when'   => $this->ago($r->updated_at ?? $r->created_at),
                ])
                ->values()
                ->all();
        } catch (\Throwable $e) {
        }

        return $out;
    }

    protected function shipping(): array
    {
        $out = ['loading' => 0, 'ready' => 0, 'dispatched' => 0, 'recent' => []];
        try {
            if (!Schema::hasTable('wms_shipments')) {
                return $out;
            }

            $rows = DB::table('wms_shipments')->get(['id', 'shipment_number', 'status', 'loaded_qty', 'expected_qty', 'updated_at', 'created_at']);

            foreach ($rows as $r) {
                $st = (string) $r->status;
                if (in_array($st, ['dispatched', 'despachado'], true)) {
                    $out['dispatched']++;
                } elseif (in_array($st, ['loaded', 'ready', 'completed', 'cargado'], true)) {
                    $out['ready']++;
                } elseif (!in_array($st, ['cancelled', 'cancelado'], true)) {
                    $out['loading']++;
                }
            }

            $out['recent'] = $rows
                ->sortByDesc(fn ($r) => $r->updated_at ?? $r->created_at)
                ->take(7)
                ->map(function ($r) {
                    $expected = max(0, (int) $r->expected_qty);
                    $loaded = max(0, (int) $r->loaded_qty);
                    $pct = $expected > 0 ? min(100, (int) round(($loaded / $expected) * 100)) : 0;

                    return [
                        'title'    => (string) ($r->shipment_number ?: ('SHIP-' . str_pad((string) $r->id, 3, '0', STR_PAD_LEFT))),
                        'status'   => $this->shipStatus((string) $r->status),
                        'progress' => $pct,
                        'when'     => $this->ago($r->updated_at ?? $r->created_at),
                    ];
                })
                ->values()
                ->all();
        } catch (\Throwable $e) {
        }

        return $out;
    }

    protected function movements(): array
    {
        $events = [];

        // ENTRADAS: productos recibidos (líneas de recepción).
        try {
            if (Schema::hasTable('wms_reception_lines')) {
                foreach (DB::table('wms_reception_lines')->orderByDesc('id')->limit(12)->get(['name', 'sku', 'quantity', 'created_at']) as $r) {
                    $events[] = [
                        'ts'      => $r->created_at,
                        'product' => (string) ($r->name ?: $r->sku ?: 'Producto'),
                        'type'    => ['label' => 'Entrada', 'dir' => 'in'],
                        'qty'     => (int) $r->quantity,
                        'when'    => $this->ago($r->created_at),
                    ];
                }
            }
        } catch (\Throwable $e) {
        }

        // SALIDAS: productos embarcados (líneas de embarque con carga).
        try {
            if (Schema::hasTable('wms_shipment_lines')) {
                $q = DB::table('wms_shipment_lines')->where('loaded_qty', '>', 0)->orderByDesc('id')->limit(12);
                $cols = ['loaded_qty', 'updated_at'];
                foreach (['product_name', 'product_sku'] as $c) {
                    if (Schema::hasColumn('wms_shipment_lines', $c)) {
                        $cols[] = $c;
                    }
                }
                foreach ($q->get($cols) as $r) {
                    $events[] = [
                        'ts'      => $r->updated_at ?? null,
                        'product' => (string) ($r->product_name ?? $r->product_sku ?? 'Producto'),
                        'type'    => ['label' => 'Salida', 'dir' => 'out'],
                        'qty'     => (int) $r->loaded_qty,
                        'when'    => $this->ago($r->updated_at ?? null),
                    ];
                }
            }
        } catch (\Throwable $e) {
        }

        // Ordena por fecha (lo más reciente arriba) y toma los últimos 9.
        usort($events, fn ($a, $b) => strcmp((string) ($b['ts'] ?? ''), (string) ($a['ts'] ?? '')));

        return array_map(function ($e) {
            unset($e['ts']);
            return $e;
        }, array_slice($events, 0, 9));
    }

    /* ================= Helpers ================= */

    protected function ago($date): string
    {
        if (!$date) {
            return '';
        }
        try {
            return Carbon::parse($date)->locale('es')->diffForHumans();
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected function receptionStatus(string $s): array
    {
        return match ($s) {
            'firmado'   => ['label' => 'Completada', 'tone' => 'ok'],
            'cancelado' => ['label' => 'Cancelada',  'tone' => 'bad'],
            default     => ['label' => 'Recibiendo', 'tone' => 'warn'],
        };
    }

    protected function pickStatus($raw): string
    {
        if (is_numeric($raw)) {
            return match ((int) $raw) {
                1 => 'in_progress',
                2 => 'completed',
                3, 9 => 'cancelled',
                default => 'pending',
            };
        }

        $s = strtolower(trim((string) $raw));
        return in_array($s, ['pending', 'in_progress', 'completed', 'cancelled'], true) ? $s : 'pending';
    }

    protected function shipStatus(string $s): array
    {
        return match ($s) {
            'dispatched', 'despachado' => ['label' => 'Despachado', 'tone' => 'ok'],
            'loaded', 'ready', 'completed', 'cargado' => ['label' => 'Listo',  'tone' => 'ok'],
            'cancelled', 'cancelado'   => ['label' => 'Cancelado', 'tone' => 'bad'],
            default                    => ['label' => 'Cargando',  'tone' => 'warn'],
        };
    }

    protected function moveType(string $t): array
    {
        $t = strtolower(trim($t));
        if (in_array($t, ['in', 'entrada', 'reception', 'receipt'], true)) {
            return ['label' => 'Entrada', 'dir' => 'in'];
        }
        if (in_array($t, ['out', 'salida', 'pick', 'ship', 'dispatch'], true)) {
            return ['label' => 'Salida', 'dir' => 'out'];
        }
        if (in_array($t, ['move', 'transfer', 'movimiento', 'traslado'], true)) {
            return ['label' => 'Traslado', 'dir' => 'move'];
        }
        return ['label' => ucfirst($t ?: 'Movimiento'), 'dir' => 'move'];
    }
}
