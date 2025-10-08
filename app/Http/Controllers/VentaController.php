<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Services\FacturaApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf; // <- Usa el facade correcto

class VentaController extends Controller
{
    /**
     * Listado de ventas con filtros:
     * - q: busca por folio (id) o nombre del cliente.
     * - cliente_id: filtra por cliente.
     * - estado / estatus: filtra por estado (acepta ambos nombres de columna).
     * - moneda: MXN, USD, etc.
     * - desde / hasta: rango de fecha (yyyy-mm-dd), compatible con columnas fecha o fecha_venta.
     * - sort: columna segura (id, fecha, total) y order: asc|desc.
     * - per_page: tamaño de página (por defecto 12).
     */
    public function index(Request $request)
    {
        $q          = trim((string) $request->query('q', ''));
        $clienteId  = $request->integer('cliente_id');
        $estado     = $request->query('estado', $request->query('estatus'));
        $moneda     = $request->query('moneda');
        $desde      = $request->query('desde'); // yyyy-mm-dd
        $hasta      = $request->query('hasta'); // yyyy-mm-dd
        $perPage    = max(1, min((int) $request->query('per_page', 12), 100));

        // Orden permitido
        $sort     = $request->query('sort', 'id');
        $order    = strtolower($request->query('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortSafe = in_array($sort, ['id', 'total', 'fecha'], true) ? $sort : 'id';

        $query = Venta::query()
            ->with(['cliente'])
            // Búsqueda por id o por nombre del cliente
            ->when($q !== '', function (Builder $qb) use ($q) {
                $qb->where(function (Builder $sub) use ($q) {
                    if (ctype_digit($q)) {
                        $sub->orWhere('id', (int) $q);
                    }
                    $sub->orWhereHas('cliente', function (Builder $c) use ($q) {
                        $c->where('nombre', 'like', '%' . $q . '%');
                    });
                });
            })
            // Filtra por cliente
            ->when($clienteId, fn (Builder $qb) => $qb->where('cliente_id', $clienteId))
            // Filtra por estado/estatus (acepta ambas columnas)
            ->when($estado !== null && $estado !== '', function (Builder $qb) use ($estado) {
                $qb->where(function (Builder $w) use ($estado) {
                    $w->orWhere('estado', $estado)
                      ->orWhere('estatus', $estado);
                });
            })
            // Filtra por moneda
            ->when($moneda, fn (Builder $qb) => $qb->where('moneda', $moneda))
            // Rango de fechas (intenta con fecha y fecha_venta)
            ->when($desde || $hasta, function (Builder $qb) use ($desde, $hasta) {
                $from = $desde ? Carbon::parse($desde)->startOfDay() : null;
                $to   = $hasta ? Carbon::parse($hasta)->endOfDay()   : null;

                $qb->where(function (Builder $w) use ($from, $to) {
                    // fecha
                    $w->when($from, fn ($q) => $q->orWhere(function ($x) use ($from) { $x->whereNotNull('fecha')->where('fecha', '>=', $from); }))
                      ->when($to,   fn ($q) => $q->orWhere(function ($x) use ($to)   { $x->whereNotNull('fecha')->where('fecha', '<=', $to);   }));

                    // fecha_venta
                    $w->when($from, fn ($q) => $q->orWhere(function ($x) use ($from) { $x->whereNotNull('fecha_venta')->where('fecha_venta', '>=', $from); }))
                      ->when($to,   fn ($q) => $q->orWhere(function ($x) use ($to)   { $x->whereNotNull('fecha_venta')->where('fecha_venta', '<=', $to);   }));
                });
            });

        // Orden
        if ($sortSafe === 'fecha') {
            $query->orderBy('fecha', $order)
                  ->orderBy('fecha_venta', $order)
                  ->orderBy('id', 'desc');
        } else {
            $query->orderBy($sortSafe, $order)
                  ->orderBy('id', 'desc');
        }

        $ventas = $query->paginate($perPage)->appends($request->query());

        return view('ventas.index', compact('ventas'));
    }

    /**
     * Detalle de venta.
     */
    public function show(Venta $venta)
    {
        $venta->loadMissing([
            'cliente',
            'items.producto',
            'plazos',
            'cotizacion',
        ]);

        return view('ventas.show', compact('venta'));
    }

    /**
     * Descarga el PDF minimalista de la venta (Blade: resources/views/ventas/pdf.blade.php).
     */
    public function pdf(Venta $venta)
    {
        $venta->loadMissing('cliente', 'items.producto', 'cotizacion');

        // Si necesitas imágenes remotas en el PDF, descomenta:
        // Pdf::setOption(['isRemoteEnabled' => true]);

        $pdf  = Pdf::loadView('ventas.pdf', compact('venta'))->setPaper('letter');
        $file = 'Venta-' . ($venta->folio ?? $venta->id) . '.pdf';

        return $pdf->download($file);
    }

    /**
     * (Opcional) Timbrar manualmente una venta desde la UI (por si el automático falla).
     * Ruta sugerida: POST /ventas/{venta}/facturar  -> name: ventas.facturar
     */
    public function facturar(Venta $venta, FacturaApiService $svc)
    {
        if ($venta->factura_uuid) {
            return back()->with('ok', 'Esta venta ya está facturada (UUID: ' . $venta->factura_uuid . ').');
        }

        try {
            $svc->facturarVenta($venta);
            $svc->guardarArchivos($venta);

            Log::info('Venta facturada manualmente', [
                'venta_id' => $venta->id,
                'uuid' => $venta->factura_uuid,
            ]);

            return redirect()->route('ventas.show', $venta)
                ->with('ok', 'Factura generada correctamente.');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'No se pudo timbrar: ' . $e->getMessage());
        }
    }
}
