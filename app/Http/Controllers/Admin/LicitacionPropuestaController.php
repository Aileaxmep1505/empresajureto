<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\MatchLicitacionPropuestaItems;
use App\Models\LicitacionPropuesta;
use App\Models\LicitacionPropuestaItem;
use App\Models\LicitacionRequestItem;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
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
     * Formulario para crear una nueva propuesta (seleccionar licitación/requisición).
     */
    public function create(Request $request)
    {
        // puedes pasar licitacion_id / requisicion_id por querystring
        $licitacionId  = $request->integer('licitacion_id');
        $requisicionId = $request->integer('requisicion_id');

        return view('admin.licitacion_propuestas.create', compact('licitacionId', 'requisicionId'));
    }

    /**
     * Crea una nueva Propuesta Económica Comparativa y
     * genera los renglones base desde licitacion_request_items.
     * Después lanza el Job para que la IA haga el match contra products.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'licitacion_id'  => ['nullable', 'integer'],
            'requisicion_id' => ['nullable', 'integer'],
            'titulo'         => ['nullable', 'string', 'max:255'],
            'moneda'         => ['nullable', 'string', 'max:10'],
            'fecha'          => ['nullable', 'date'],
        ]);

        $licitacionId  = $data['licitacion_id'] ?? null;
        $requisicionId = $data['requisicion_id'] ?? null;

        $propuesta = LicitacionPropuesta::create([
            'licitacion_id'  => $licitacionId,
            'requisicion_id' => $requisicionId,
            'codigo'         => $this->generarCodigo(),
            'titulo'         => $data['titulo'] ?? 'Propuesta económica comparativa',
            'moneda'         => $data['moneda'] ?? 'MXN',
            'fecha'          => $data['fecha'] ?? now()->toDateString(),
            'status'         => 'draft',
        ]);

        // Trae todos los items extraídos para esa licitación / requisición
        $itemsQuery = LicitacionRequestItem::query();

        if ($licitacionId) {
            $itemsQuery->where('licitacion_id', $licitacionId);
        }

        if ($requisicionId) {
            $itemsQuery->where('requisicion_id', $requisicionId);
        }

        $requestItems = $itemsQuery->orderBy('renglon')->get();

        foreach ($requestItems as $item) {
            LicitacionPropuestaItem::create([
                'licitacion_propuesta_id'    => $propuesta->id,
                'licitacion_request_item_id' => $item->id,
                'product_id'                 => null, // lo llenará después la IA o el usuario
                'match_score'                => null,
                'motivo_seleccion'           => null,
                'unidad_propuesta'           => $item->unidad,
                'cantidad_propuesta'         => $item->cantidad,
                'precio_unitario'            => 0,
                'subtotal'                   => 0,
                'notas'                      => null,
            ]);
        }

        // Lanza el Job para que la IA busque coincidencias con tus productos
        MatchLicitacionPropuestaItems::dispatch($propuesta);

        // Redirección flexible según cómo nombres tus rutas
        $routeName = null;

        if (Route::has('admin.licitacion-propuestas.show')) {
            $routeName = 'admin.licitacion-propuestas.show';
        } elseif (Route::has('licitacion-propuestas.show')) {
            $routeName = 'licitacion-propuestas.show';
        }

        if ($routeName) {
            return redirect()
                ->route($routeName, $propuesta)
                ->with('status', 'Propuesta creada. La IA está sugiriendo productos y precios en segundo plano.');
        }

        return redirect()
            ->back()
            ->with('status', 'Propuesta creada. La IA está sugiriendo productos y precios en segundo plano.');
    }

    /**
     * Muestra la propuesta con su cuadro comparativo (solicitado vs ofertado).
     */
    public function show(LicitacionPropuesta $licitacionPropuesta)
    {
        $licitacionPropuesta->load(['items.requestItem', 'items.product']);

        return view('admin.licitacion_propuestas.show', [
            'propuesta' => $licitacionPropuesta,
        ]);
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
