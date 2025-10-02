<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

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
        $sort       = $request->query('sort', 'id');
        $order      = strtolower($request->query('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortSafe   = in_array($sort, ['id','total','fecha'], true) ? $sort : 'id';

        $query = Venta::query()
            ->with(['cliente'])
            // Búsqueda simple por id o nombre de cliente
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
            ->when($clienteId, function (Builder $qb) use ($clienteId) {
                $qb->where('cliente_id', $clienteId);
            })
            // Filtra por estado/estatus (la tabla podría usar cualquiera)
            ->when($estado !== null && $estado !== '', function (Builder $qb) use ($estado) {
                $qb->where(function (Builder $w) use ($estado) {
                    $w->orWhere('estado', $estado)
                      ->orWhere('estatus', $estado);
                });
            })
            // Filtra por moneda
            ->when($moneda, function (Builder $qb) use ($moneda) {
                $qb->where('moneda', $moneda);
            })
            // Rango de fechas (acepta columnas 'fecha' o 'fecha_venta')
            ->when($desde || $hasta, function (Builder $qb) use ($desde, $hasta) {
                $from = $desde ? Carbon::parse($desde)->startOfDay() : null;
                $to   = $hasta ? Carbon::parse($hasta)->endOfDay()   : null;

                $qb->where(function (Builder $w) use ($from, $to) {
                    // fecha
                    $w->when($from, fn($q) => $q->orWhere(function($x) use ($from){ $x->whereNotNull('fecha')->where('fecha', '>=', $from); }))
                      ->when($to,   fn($q) => $q->orWhere(function($x) use ($to)  { $x->whereNotNull('fecha')->where('fecha', '<=', $to);   }));

                    // fecha_venta
                    $w->when($from, fn($q) => $q->orWhere(function($x) use ($from){ $x->whereNotNull('fecha_venta')->where('fecha_venta', '>=', $from); }))
                      ->when($to,   fn($q) => $q->orWhere(function($x) use ($to)  { $x->whereNotNull('fecha_venta')->where('fecha_venta', '<=', $to);   }));
                });
            });

        // Ordenar: si piden 'fecha', intentamos ordenar por fecha o fecha_venta
        if ($sortSafe === 'fecha') {
            // Preferimos 'fecha', si no existe en el schema igualmente no romperá;
            // para mayor seguridad podrías verificar con Schema::hasColumn(...)
            $query->orderBy('fecha', $order)->orderBy('fecha_venta', $order)->orderBy('id', 'desc');
        } else {
            $query->orderBy($sortSafe, $order)->orderBy('id', 'desc');
        }

        $ventas = $query->paginate($perPage)->appends($request->query());

        return view('ventas.index', compact('ventas'));
    }

    /**
     * Detalle de venta.
     * Cargamos relaciones sin fallar si alguna no existe aún (loadMissing).
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
}
