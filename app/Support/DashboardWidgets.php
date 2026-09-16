<?php

namespace App\Support;

use App\Models\AccountReceivable;
use App\Models\AgendaEvent;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Cotizacion;
use App\Models\ManualInvoice;
use App\Models\Order;
use App\Models\Project;
use App\Models\PropuestaComercial;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\Route;

/**
 * Catálogo de tarjetas del tablero.
 *
 * Cada tarjeta declara aquí su nombre, su descripción, qué tan chica o
 * grande puede quedar, y cómo se calculan sus datos. Agregar una tarjeta
 * nueva es agregar una entrada en catalogo() y su vista en dashboard/widgets.
 *
 * El tamaño se mide en celdas sobre una rejilla de 4 columnas:
 *   w = columnas de ancho (1 a 4)
 *   h = renglones de alto (cada renglón mide --dash-row en la hoja de estilos)
 */
class DashboardWidgets
{
    public const ANCHO_MAX = 4;
    public const ALTO_MAX = 8;

    /** Límites por omisión de cualquier tarjeta que no diga otra cosa. */
    private const LIMITES = ['w_min' => 1, 'w_max' => self::ANCHO_MAX, 'h_min' => 2, 'h_max' => self::ALTO_MAX];

    /** Etapas de una licitación (proyecto), igual que en el tablero kanban. */
    public const ETAPAS = [
        'analisis_bases'     => 'Análisis de Bases',
        'revision'           => 'Revisión',
        'participa'          => 'Participa',
        'junta_aclaraciones' => 'Junta de Aclaraciones',
        'armado_propuesta'   => 'Armado de Propuesta',
        'entrega'            => 'Entrega',
        'no_participa'       => 'No participa',
        'ganado'             => 'Ganado',
        'perdido'            => 'Perdido',
        'desierta'           => 'Desierta',
    ];

    /** Etapas que ya no están "en juego". */
    private const ETAPAS_CERRADAS = ['ganado', 'perdido', 'desierta', 'no_participa'];

    /** Estados de ticket que ya no cuentan como pendientes. */
    private const TICKETS_CERRADOS = ['completado', 'done', 'cancelado', 'closed'];

    /** Estados de venta / cuenta que no suman. */
    private const CANCELADAS = ['cancelada', 'cancelado', 'cancelled'];

    private const COBRADAS = ['paid', 'pagado', 'pagada', 'cancelled', 'cancelada', 'cancelado'];

    /**
     * Arreglo de fábrica, para quien todavía no personaliza su tablero.
     *
     * @return array<int, array{id: string, w: int, h: int}>
     */
    public static function porOmision(User $user): array
    {
        $ids = self::restringido($user)
            ? ['accesos_manager']
            : ['licitaciones', 'cotizaciones', 'ventas_mes', 'tickets',
                'accesos_rapidos', 'ultimas_licitaciones',
                'ventas_grafica', 'agenda_proxima',
                'accesos_licitaciones', 'accesos_finanzas'];

        return array_map(function (string $id) {
            $def = self::definicion($id);

            return ['id' => $id, 'w' => $def['w'], 'h' => $def['h']];
        }, $ids);
    }

    /**
     * Un manager sin rol de admin ve un tablero reducido, igual que en el
     * menú completo.
     */
    public static function restringido(User $user): bool
    {
        if (! method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole('manager') && ! $user->hasRole('admin');
    }

    /** Definición de una tarjeta con sus límites ya completados. */
    public static function definicion(string $id): array
    {
        $def = self::catalogo()[$id] ?? [];

        return array_merge(self::LIMITES, $def);
    }

    /**
     * Definición de cada tarjeta disponible.
     *
     * Claves opcionales:
     *   vista   = parcial a usar cuando no se llama igual que el id
     *   accesos = clave del grupo de DashboardAccesos que pinta
     *   solo_restringido = true si es exclusiva del tablero reducido
     *
     * @return array<string, array<string, mixed>>
     */
    public static function catalogo(): array
    {
        return [
            // ---------- Indicadores ----------
            'licitaciones' => [
                'titulo' => 'Licitaciones',
                'descripcion' => 'Cuántas hay en juego, cuántas ganadas y en qué etapa van.',
                'grupo' => 'Indicadores',
                'w' => 1, 'h' => 2,
            ],
            'cotizaciones' => [
                'titulo' => 'Cotizaciones',
                'descripcion' => 'Cuántas llevas, por qué monto y cuántas siguen abiertas.',
                'grupo' => 'Indicadores',
                'w' => 1, 'h' => 2,
            ],
            'ventas_mes' => [
                'titulo' => 'Ventas del mes',
                'descripcion' => 'Monto vendido en el mes en curso y comparación con el anterior.',
                'grupo' => 'Indicadores',
                'w' => 1, 'h' => 2,
            ],
            'pedidos_web' => [
                'titulo' => 'Pedidos web',
                'descripcion' => 'Pedidos de la tienda en línea y cuántos van este mes.',
                'grupo' => 'Indicadores',
                'w' => 1, 'h' => 2,
            ],
            'tickets' => [
                'titulo' => 'Tickets',
                'descripcion' => 'Pendientes, cuántos son tuyos y cuántos ya se vencieron.',
                'grupo' => 'Indicadores',
                'w' => 1, 'h' => 2,
            ],
            'facturas' => [
                'titulo' => 'Facturas',
                'descripcion' => 'Facturas válidas, borradores y lo facturado en el mes.',
                'grupo' => 'Indicadores',
                'w' => 1, 'h' => 2,
            ],
            'catalogo' => [
                'titulo' => 'Productos',
                'descripcion' => 'Productos dados de alta, publicados y cuáles andan bajos de stock.',
                'grupo' => 'Indicadores',
                'w' => 1, 'h' => 2,
            ],
            'clientes' => [
                'titulo' => 'Clientes',
                'descripcion' => 'Total registrado y cuántos entraron este mes.',
                'grupo' => 'Indicadores',
                'w' => 1, 'h' => 2,
            ],
            'por_cobrar' => [
                'titulo' => 'Cuentas por cobrar',
                'descripcion' => 'Lo que sigue pendiente de cobro y cuánto ya venció.',
                'grupo' => 'Indicadores',
                'w' => 1, 'h' => 2,
            ],

            // ---------- Gráficas ----------
            'ventas_grafica' => [
                'titulo' => 'Ventas por mes',
                'descripcion' => 'Barras de los últimos seis meses.',
                'grupo' => 'Gráficas',
                'w' => 2, 'h' => 4, 'w_min' => 2, 'h_min' => 3,
            ],

            // ---------- Listas ----------
            'ultimas_licitaciones' => [
                'titulo' => 'Últimas licitaciones',
                'descripcion' => 'Las más recientes, con su etapa.',
                'grupo' => 'Listas',
                'w' => 2, 'h' => 4, 'h_min' => 3,
            ],
            'ultimas_cotizaciones' => [
                'titulo' => 'Últimas cotizaciones',
                'descripcion' => 'Las más recientes, con su cliente y estado.',
                'grupo' => 'Listas',
                'w' => 2, 'h' => 4, 'h_min' => 3,
            ],
            'ultimos_pedidos' => [
                'titulo' => 'Últimos pedidos web',
                'descripcion' => 'Los pedidos más recientes de la tienda.',
                'grupo' => 'Listas',
                'w' => 2, 'h' => 4, 'h_min' => 3,
            ],
            'mis_tickets' => [
                'titulo' => 'Mis tickets',
                'descripcion' => 'Tus tickets pendientes, primero los que vencen antes.',
                'grupo' => 'Listas',
                'w' => 2, 'h' => 4, 'h_min' => 3,
            ],
            'agenda_proxima' => [
                'titulo' => 'Agenda',
                'descripcion' => 'Lo que viene en tu agenda a partir de hoy.',
                'grupo' => 'Listas',
                'w' => 2, 'h' => 4, 'h_min' => 3,
            ],
            'propuestas_recientes' => [
                'titulo' => 'Propuestas recientes',
                'descripcion' => 'Últimas propuestas comerciales generadas.',
                'grupo' => 'Listas',
                'w' => 2, 'h' => 4, 'h_min' => 3,
            ],

            // ---------- Accesos directos ----------
            'accesos_rapidos' => [
                'titulo' => 'Acciones rápidas',
                'descripcion' => 'Botones para crear lo que más se usa.',
                'grupo' => 'Accesos directos',
                'w' => 2, 'h' => 3,
            ],
            'accesos_finanzas' => [
                'titulo' => 'Finanzas y Ventas',
                'descripcion' => 'Cotizaciones, ventas, facturas, gastos y part. contable.',
                'grupo' => 'Accesos directos',
                'w' => 2, 'h' => 3, 'vista' => '_accesos', 'accesos' => 'finanzas',
            ],
            'accesos_inventario' => [
                'titulo' => 'Inventario y Productos',
                'descripcion' => 'Productos, catálogo, fichas técnicas y almacén.',
                'grupo' => 'Accesos directos',
                'w' => 2, 'h' => 3, 'vista' => '_accesos', 'accesos' => 'inventario',
            ],
            'accesos_operaciones' => [
                'titulo' => 'Operaciones',
                'descripcion' => 'Vehículos, logística y agenda.',
                'grupo' => 'Accesos directos',
                'w' => 2, 'h' => 2, 'vista' => '_accesos', 'accesos' => 'operaciones',
            ],
            'accesos_clientes' => [
                'titulo' => 'Clientes y Comunicación',
                'descripcion' => 'Clientes, proveedores, WhatsApp, help desk y correo.',
                'grupo' => 'Accesos directos',
                'w' => 2, 'h' => 3, 'vista' => '_accesos', 'accesos' => 'clientes',
            ],
            'accesos_licitaciones' => [
                'titulo' => 'Licitaciones',
                'descripcion' => 'Centro de control, tablero, tabla IA, PDFs y propuestas.',
                'grupo' => 'Accesos directos',
                'w' => 2, 'h' => 3, 'vista' => '_accesos', 'accesos' => 'licitaciones',
            ],
            'accesos_tickets' => [
                'titulo' => 'Tickets',
                'descripcion' => 'Todos los tickets, los tuyos y crear uno nuevo.',
                'grupo' => 'Accesos directos',
                'w' => 2, 'h' => 2, 'vista' => '_accesos', 'accesos' => 'tickets',
            ],
            'accesos_admin' => [
                'titulo' => 'Administración y Control',
                'descripcion' => 'Contabilidad, documentación, usuarios y pedidos web.',
                'grupo' => 'Accesos directos',
                'w' => 2, 'h' => 3, 'vista' => '_accesos', 'accesos' => 'admin',
            ],
            'accesos_manager' => [
                'titulo' => 'Accesos',
                'descripcion' => 'Mi perfil, part. contable y documentación de altas.',
                'grupo' => 'Accesos directos',
                'w' => 2, 'h' => 2, 'vista' => '_accesos', 'accesos' => 'manager',
                'solo_restringido' => true,
            ],
        ];
    }

    /**
     * Tarjetas que un usuario puede tener en su tablero.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function catalogoPara(User $user): array
    {
        $restringido = self::restringido($user);

        return array_filter(self::catalogo(), function (array $def) use ($restringido) {
            $exclusiva = ! empty($def['solo_restringido']);

            return $restringido ? $exclusiva : ! $exclusiva;
        });
    }

    /**
     * Limpia lo que venga guardado o del formulario: descarta ids que ya no
     * existen o que el usuario no puede ver, recorta tamaños fuera de rango
     * y quita repetidos.
     *
     * @return array<int, array{id: string, w: int, h: int}>
     */
    public static function normalizar(mixed $lista, User $user): array
    {
        if (! is_array($lista)) {
            return [];
        }

        $catalogo = self::catalogoPara($user);
        $vistos = [];
        $salida = [];

        foreach ($lista as $item) {
            $id = is_array($item) ? ($item['id'] ?? null) : null;

            if (! is_string($id) || ! isset($catalogo[$id]) || isset($vistos[$id])) {
                continue;
            }

            $def = self::definicion($id);

            $salida[] = [
                'id' => $id,
                'w' => self::recortar($item['w'] ?? $def['w'], $def['w_min'], $def['w_max']),
                'h' => self::recortar($item['h'] ?? $def['h'], $def['h_min'], $def['h_max']),
            ];

            $vistos[$id] = true;
        }

        return $salida;
    }

    /** Deja el número dentro del rango; lo que no sea número cae al mínimo. */
    private static function recortar(mixed $valor, int $min, int $max): int
    {
        if (! is_numeric($valor)) {
            return $min;
        }

        return max($min, min($max, (int) $valor));
    }

    /**
     * Tarjetas que le tocan a un usuario, ya normalizadas.
     *
     * @return array<int, array{id: string, w: int, h: int}>
     */
    public static function paraUsuario(User $user): array
    {
        $guardado = self::normalizar($user->dashboard_widgets, $user);

        return $guardado ?: self::porOmision($user);
    }

    /**
     * Qué tanto detalle admite una tarjeta según lo que ocupa.
     *
     *   1 = apretada, solo el dato principal
     *   2 = hay lugar para un desglose
     *   3 = amplia, cabe además una tabla
     */
    public static function nivel(int $w, int $h): int
    {
        $area = $w * $h;

        return match (true) {
            $area >= 8 => 3,
            $area >= 4 => 2,
            default => 1,
        };
    }

    /**
     * Cuántas filas caben en una lista según el alto.
     *
     * Es una estimación calibrada contra el alto real de fila (~53px) y el
     * espacio que se lleva el encabezado. Se queda corta a propósito: más
     * vale que sobre tantito a que la última fila salga cortada.
     */
    public static function filasQueCaben(int $h): int
    {
        return max(2, (int) floor(($h - 1) * 1.4));
    }

    /** URL de una ruta con nombre, o nulo si la ruta no existe. */
    public static function url(string $ruta, mixed $parametros = []): ?string
    {
        return Route::has($ruta) ? route($ruta, $parametros) : null;
    }

    /**
     * Calcula los datos de una tarjeta. Se llama solo para las visibles y
     * con su tamaño, así que las consultas del detalle extra solo corren
     * cuando la tarjeta es lo bastante grande para mostrarlo.
     */
    public static function datos(string $id, User $user, int $w = 1, int $h = 2): array
    {
        $nivel = self::nivel($w, $h);
        $filas = self::filasQueCaben($h);
        $def = self::definicion($id);

        // Todas las tarjetas de accesos comparten cálculo: el grupo que pintan.
        if (! empty($def['accesos'])) {
            $grupo = DashboardAccesos::grupo($def['accesos']);

            return ['items' => $grupo['items'] ?? [], 'icono' => $grupo['icono'] ?? 'apps', 'nivel' => $nivel];
        }

        $datos = match ($id) {
            'licitaciones' => self::datosLicitaciones($nivel, $filas),
            'cotizaciones' => self::datosCotizaciones($nivel, $filas),
            'ventas_mes' => self::datosVentasMes($nivel, $filas),
            'pedidos_web' => self::datosPedidosWeb($nivel, $filas),
            'tickets' => self::datosTickets($user, $nivel, $filas),
            'facturas' => self::datosFacturas($nivel, $filas),
            'catalogo' => self::datosCatalogo($nivel, $filas),
            'clientes' => self::datosClientes($nivel, $filas),
            'por_cobrar' => self::datosPorCobrar($nivel, $filas),
            'ventas_grafica' => self::datosVentasGrafica($nivel),
            'ultimas_licitaciones' => self::datosUltimasLicitaciones($filas),
            'ultimas_cotizaciones' => self::datosUltimasCotizaciones($filas),
            'ultimos_pedidos' => self::datosUltimosPedidos($filas),
            'mis_tickets' => self::datosMisTickets($user, $filas),
            'agenda_proxima' => self::datosAgendaProxima($user, $filas),
            'propuestas_recientes' => self::datosPropuestasRecientes($filas),
            'accesos_rapidos' => ['items' => DashboardAccesos::accionesRapidas($nivel)],
            default => [],
        };

        return $datos + ['nivel' => $nivel];
    }

    // ===================== Cálculos =====================

    private static function datosLicitaciones(int $nivel, int $filas): array
    {
        $base = fn () => Project::whereNull('archived_at');

        $datos = [
            'total' => $base()->count(),
            'en_juego' => $base()->where(function ($q) {
                $q->whereNull('workflow_status')->orWhereNotIn('workflow_status', self::ETAPAS_CERRADAS);
            })->count(),
            'ganadas' => $base()->where('workflow_status', 'ganado')->count(),
        ];

        if ($nivel >= 2) {
            $datos['participa'] = $base()->whereIn('workflow_status', ['participa', 'junta_aclaraciones', 'armado_propuesta', 'entrega'])->count();
            $datos['analisis'] = $base()->where(function ($q) {
                $q->whereNull('workflow_status')->orWhereIn('workflow_status', ['analisis_bases', 'revision']);
            })->count();
            $datos['perdidas'] = $base()->whereIn('workflow_status', ['perdido', 'desierta'])->count();
        }

        if ($nivel >= 3) {
            $datos['tabla'] = $base()->selectRaw('workflow_status, COUNT(*) as total')
                ->groupBy('workflow_status')
                ->orderByDesc('total')
                ->limit($filas)
                ->get()
                ->map(fn ($fila) => [
                    'etiqueta' => self::ETAPAS[$fila->workflow_status] ?? self::ETAPAS['analisis_bases'],
                    'valor' => $fila->total,
                ])
                ->all();
        }

        return $datos;
    }

    private static function datosCotizaciones(int $nivel, int $filas): array
    {
        $datos = [
            'total' => Cotizacion::count(),
            'monto' => (float) Cotizacion::sum('total'),
            'abiertas' => Cotizacion::where(function ($q) {
                $q->whereNull('estado')->orWhereIn('estado', ['', 'abierta', 'open']);
            })->count(),
        ];

        if ($nivel >= 2) {
            $datos['mes'] = Cotizacion::where('created_at', '>=', now()->startOfMonth())->count();
            $datos['convertidas'] = Cotizacion::where('estado', 'converted')->count();
            $datos['promedio'] = $datos['total'] > 0 ? $datos['monto'] / $datos['total'] : 0.0;
        }

        if ($nivel >= 3) {
            $datos['tabla'] = Cotizacion::selectRaw('estado, COUNT(*) as total, SUM(total) as monto')
                ->groupBy('estado')
                ->orderByDesc('total')
                ->limit($filas)
                ->get()
                ->map(fn ($fila) => [
                    'etiqueta' => self::etiquetaCotizacion($fila->estado),
                    'valor' => $fila->total,
                    'extra' => '$' . number_format((float) $fila->monto, 2),
                ])
                ->all();
        }

        return $datos;
    }

    private static function etiquetaCotizacion(?string $estado): string
    {
        return match ($estado) {
            'converted' => 'Convertida',
            'cancelled' => 'Cancelada',
            'abierta', 'open', null, '' => 'Abierta',
            default => ucfirst($estado),
        };
    }

    private static function datosVentasMes(int $nivel, int $filas): array
    {
        $desde = now()->startOfMonth();
        $activas = fn () => Venta::whereNotIn('estado', self::CANCELADAS);

        $datos = [
            'monto' => (float) $activas()->where('created_at', '>=', $desde)->sum('total'),
            'cantidad' => $activas()->where('created_at', '>=', $desde)->count(),
            'monto_total' => (float) $activas()->sum('total'),
        ];

        if ($nivel >= 2) {
            $inicioAnterior = (clone $desde)->subMonth();
            $anterior = (float) $activas()->whereBetween('created_at', [$inicioAnterior, (clone $inicioAnterior)->endOfMonth()])->sum('total');

            $datos['mes_anterior'] = $anterior;
            $datos['variacion'] = $anterior > 0 ? (($datos['monto'] - $anterior) / $anterior) * 100 : null;
            $datos['ganancia'] = (float) $activas()->where('created_at', '>=', $desde)->sum('ganancia_estimada');
        }

        if ($nivel >= 3) {
            $datos['tabla'] = $activas()
                ->with('cliente')
                ->where('created_at', '>=', $desde)
                ->orderByDesc('total')
                ->limit($filas)
                ->get()
                ->map(fn ($venta) => [
                    'etiqueta' => $venta->cliente->nombre ?? ('Venta #' . $venta->id),
                    'extra' => $venta->folio_display ?? ('#' . $venta->id),
                    'valor' => '$' . number_format((float) $venta->total, 2),
                ])
                ->all();
        }

        return $datos;
    }

    private static function datosPedidosWeb(int $nivel, int $filas): array
    {
        $desde = now()->startOfMonth();

        $datos = [
            'total' => Order::count(),
            'mes' => Order::where('created_at', '>=', $desde)->count(),
            'monto_mes' => (float) Order::where('created_at', '>=', $desde)->sum('total'),
        ];

        if ($nivel >= 2) {
            $datos['pagados'] = Order::whereIn('status', ['pagado', 'paid'])->count();
            $datos['sin_envio'] = Order::whereIn('status', ['pagado', 'paid'])->whereNull('shipment_status')->count();
            $datos['monto_total'] = (float) Order::sum('total');
        }

        if ($nivel >= 3) {
            $datos['tabla'] = Order::latest()
                ->limit($filas)
                ->get()
                ->map(fn ($o) => [
                    'etiqueta' => $o->customer_name ?: ('Pedido #' . $o->id),
                    'extra' => ucfirst($o->status ?: '—'),
                    'valor' => '$' . number_format((float) $o->total, 2),
                ])
                ->all();
        }

        return $datos;
    }

    private static function datosTickets(User $user, int $nivel, int $filas): array
    {
        $abiertos = fn () => Ticket::whereNotIn('status', self::TICKETS_CERRADOS);

        $datos = [
            'abiertos' => $abiertos()->count(),
            'mios' => $abiertos()->where('assignee_id', $user->id)->count(),
            'vencidos' => $abiertos()->whereNotNull('due_at')->where('due_at', '<', now())->count(),
        ];

        if ($nivel >= 2) {
            $datos['total'] = Ticket::count();
            $datos['completados_mes'] = Ticket::whereIn('status', ['completado', 'done'])
                ->where('updated_at', '>=', now()->startOfMonth())->count();
            $datos['creados_mes'] = Ticket::where('created_at', '>=', now()->startOfMonth())->count();
        }

        if ($nivel >= 3) {
            $datos['tabla'] = Ticket::selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->orderByDesc('total')
                ->limit($filas)
                ->get()
                ->map(fn ($fila) => [
                    'etiqueta' => ucfirst(str_replace('_', ' ', $fila->status ?: 'Sin estado')),
                    'valor' => $fila->total,
                ])
                ->all();
        }

        return $datos;
    }

    private static function datosFacturas(int $nivel, int $filas): array
    {
        $desde = now()->startOfMonth();

        $datos = [
            'validas' => ManualInvoice::where('status', 'valid')->count(),
            'borradores' => ManualInvoice::where('status', 'draft')->count(),
            'monto_mes' => (float) ManualInvoice::where('status', 'valid')->where('created_at', '>=', $desde)->sum('total'),
        ];

        if ($nivel >= 2) {
            $datos['total'] = ManualInvoice::count();
            $datos['canceladas'] = ManualInvoice::whereIn('status', ['cancelled', 'pending_cancel'])->count();
            $datos['mes'] = ManualInvoice::where('created_at', '>=', $desde)->count();
        }

        if ($nivel >= 3) {
            $datos['tabla'] = ManualInvoice::latest()
                ->limit($filas)
                ->get()
                ->map(fn ($f) => [
                    'etiqueta' => $f->receiver_name ?: ($f->serie_folio ?: 'Factura #' . $f->id),
                    'extra' => $f->status_label,
                    'valor' => '$' . number_format((float) $f->total, 2),
                ])
                ->all();
        }

        return $datos;
    }

    private static function datosCatalogo(int $nivel, int $filas): array
    {
        $datos = [
            'total' => CatalogItem::count(),
            'publicados' => CatalogItem::where('status', 1)->count(),
            'sin_stock' => CatalogItem::where('stock', '<=', 0)->count(),
        ];

        if ($nivel >= 2) {
            $datos['bajo_stock'] = CatalogItem::where('stock', '>', 0)
                ->where('stock_min', '>', 0)
                ->whereColumn('stock', '<=', 'stock_min')
                ->count();
            $datos['unidades'] = (int) CatalogItem::sum('stock');
            $datos['muestras'] = CatalogItem::where('is_sample', true)->count();
        }

        if ($nivel >= 3) {
            $datos['tabla'] = CatalogItem::orderBy('stock')
                ->orderBy('name')
                ->limit($filas)
                ->get()
                ->map(fn ($p) => [
                    'etiqueta' => $p->name ?: ($p->sku ?: 'Producto #' . $p->id),
                    'extra' => $p->sku ?: '',
                    'valor' => (int) $p->stock . ' pz',
                    'alerta' => (int) $p->stock <= 0,
                ])
                ->all();
        }

        return $datos;
    }

    private static function datosClientes(int $nivel, int $filas): array
    {
        $datos = [
            'total' => Client::count(),
            'nuevos' => Client::where('created_at', '>=', now()->startOfMonth())->count(),
            'inactivos' => Client::where('estatus', false)->count(),
        ];

        if ($nivel >= 2) {
            $datos['activos'] = Client::where('estatus', true)->count();
            $datos['gobierno'] = Client::where('tipo_cliente', 'gobierno')->count();
            $datos['empresas'] = Client::where('tipo_cliente', 'empresa')->count();
        }

        if ($nivel >= 3) {
            $datos['tabla'] = Client::selectRaw('tipo_cliente, COUNT(*) as total')
                ->groupBy('tipo_cliente')
                ->orderByDesc('total')
                ->limit($filas)
                ->get()
                ->map(fn ($fila) => [
                    'etiqueta' => match ($fila->tipo_cliente) {
                        'gobierno' => 'Gobierno',
                        'empresa' => 'Empresa',
                        'particular' => 'Particular',
                        'otro' => 'Otro',
                        default => 'Sin tipo',
                    },
                    'valor' => $fila->total,
                ])
                ->all();
        }

        return $datos;
    }

    private static function datosPorCobrar(int $nivel, int $filas): array
    {
        $pendientes = fn () => AccountReceivable::whereNotIn('status', self::COBRADAS);

        $datos = [
            'cuentas' => $pendientes()->count(),
            'monto' => (float) $pendientes()->selectRaw('COALESCE(SUM(amount - COALESCE(amount_paid, 0)), 0) as p')->value('p'),
            'vencidas' => $pendientes()->whereNotNull('due_date')->whereDate('due_date', '<', now())->count(),
        ];

        if ($nivel >= 2) {
            $datos['monto_vencido'] = (float) $pendientes()->whereNotNull('due_date')->whereDate('due_date', '<', now())
                ->selectRaw('COALESCE(SUM(amount - COALESCE(amount_paid, 0)), 0) as p')->value('p');
            $datos['cobrado_mes'] = (float) AccountReceivable::whereNotNull('payment_date')
                ->whereDate('payment_date', '>=', now()->startOfMonth())->sum('amount_paid');
        }

        if ($nivel >= 3) {
            $datos['tabla'] = $pendientes()
                ->orderByRaw('due_date IS NULL, due_date ASC')
                ->limit($filas)
                ->get()
                ->map(fn ($c) => [
                    'etiqueta' => $c->client_name ?: ($c->folio ?: 'Cuenta #' . $c->id),
                    'extra' => $c->due_date ? 'Vence ' . \Illuminate\Support\Carbon::parse($c->due_date)->format('d/m/Y') : 'Sin fecha',
                    'valor' => '$' . number_format((float) $c->amount - (float) $c->amount_paid, 2),
                    'alerta' => $c->due_date && \Illuminate\Support\Carbon::parse($c->due_date)->isPast(),
                ])
                ->all();
        }

        return $datos;
    }

    private static function datosVentasGrafica(int $nivel): array
    {
        $meses = collect(range(5, 0))->map(function (int $atras) {
            $inicio = now()->startOfMonth()->subMonths($atras);
            $fin = (clone $inicio)->endOfMonth();

            return [
                'etiqueta' => ucfirst($inicio->locale('es')->isoFormat('MMM')),
                'monto' => (float) Venta::whereNotIn('estado', self::CANCELADAS)->whereBetween('created_at', [$inicio, $fin])->sum('total'),
            ];
        })->all();

        $maximo = collect($meses)->max('monto') ?: 0;

        $datos = ['meses' => $meses, 'maximo' => $maximo];

        if ($nivel >= 3) {
            // Grande, además de las barras se listan las cifras del periodo.
            $datos['total_periodo'] = array_sum(array_column($meses, 'monto'));
            $datos['tabla'] = array_map(fn ($mes) => [
                'etiqueta' => $mes['etiqueta'],
                'valor' => '$' . number_format($mes['monto'], 2),
            ], array_reverse($meses));
        }

        return $datos;
    }

    // En las listas, cuántas filas se traen depende del alto de la tarjeta.

    private static function datosUltimasLicitaciones(int $filas): array
    {
        return [
            'filas' => Project::whereNull('archived_at')->latest()->limit($filas)->get(),
        ];
    }

    private static function datosUltimasCotizaciones(int $filas): array
    {
        return [
            'filas' => Cotizacion::with('cliente')->latest()->limit($filas)->get(),
        ];
    }

    private static function datosUltimosPedidos(int $filas): array
    {
        return [
            'filas' => Order::latest()->limit($filas)->get(),
        ];
    }

    private static function datosMisTickets(User $user, int $filas): array
    {
        return [
            'filas' => Ticket::whereNotIn('status', self::TICKETS_CERRADOS)
                ->where(function ($q) use ($user) {
                    $q->where('assignee_id', $user->id)->orWhere('created_by', $user->id);
                })
                ->orderByRaw('due_at IS NULL, due_at ASC')
                ->latest()
                ->limit($filas)
                ->get(),
        ];
    }

    private static function datosAgendaProxima(User $user, int $filas): array
    {
        return [
            'filas' => AgendaEvent::where('completed', false)
                ->where('start_at', '>=', now()->startOfDay()->format('Y-m-d H:i:s'))
                // Eventos sin gente asignada son de todos; con gente, solo si estás.
                ->where(function ($q) use ($user) {
                    $q->whereNull('user_ids')
                        ->orWhereJsonLength('user_ids', 0)
                        ->orWhereJsonContains('user_ids', $user->id)
                        ->orWhereJsonContains('user_ids', (string) $user->id);
                })
                ->orderBy('start_at')
                ->limit($filas)
                ->get(),
        ];
    }

    private static function datosPropuestasRecientes(int $filas): array
    {
        return [
            'filas' => PropuestaComercial::latest()->limit($filas)->get(),
        ];
    }
}
