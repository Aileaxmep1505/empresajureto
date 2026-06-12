<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\WmsPickingController;
use App\Models\CatalogItem;
use App\Models\Inventory;
use App\Models\PropuestaResultado;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class PropuestaPickingController extends Controller
{
    /** Pantalla de empate: partidas ganadas → producto del catálogo (búsqueda manual). */
    public function empate(PropuestaResultado $resultado)
    {
        $partidas = $this->partidasGanadas($resultado);
        $folio = 'PROP-' . str_pad((string) $resultado->id, 6, '0', STR_PAD_LEFT);
        $cliente = $resultado->cliente ?: optional($resultado->propuesta)->cliente ?: 'PUBLICO EN GENERAL';
        $users = User::query()->orderBy('name')->get(['id', 'name']);

        return view('propuestas_comerciales.picking-empate', compact('resultado', 'partidas', 'folio', 'cliente', 'users'));
    }

    /** AJAX: búsqueda manual de productos. Si q viene vacío, devuelve todo el catálogo (paginado). */
    public function buscarProducto(Request $request, PropuestaResultado $resultado)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $q = trim((string) ($data['q'] ?? ''));

        $cols = ['id', 'name', 'sku'];
        foreach (['barcode', 'code', 'meli_gtin', 'stock', 'brand_name', 'brand', 'location_code', 'primary_location_id', 'image', 'image_url', 'photo', 'thumbnail'] as $c) {
            if (Schema::hasColumn('catalog_items', $c) && !in_array($c, $cols, true)) {
                $cols[] = $c;
            }
        }

        $imageCol = null;
        foreach (['image', 'image_url', 'photo', 'thumbnail'] as $c) {
            if (Schema::hasColumn('catalog_items', $c)) {
                $imageCol = $c;
                break;
            }
        }

        $query = CatalogItem::query();

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                   ->orWhere('sku', 'like', "%{$q}%");
                if (Schema::hasColumn('catalog_items', 'barcode')) {
                    $qq->orWhere('barcode', 'like', "%{$q}%");
                }
                if (Schema::hasColumn('catalog_items', 'code')) {
                    $qq->orWhere('code', 'like', "%{$q}%");
                }
                if (Schema::hasColumn('catalog_items', 'meli_gtin')) {
                    $qq->orWhere('meli_gtin', 'like', "%{$q}%");
                }
                if (ctype_digit($q)) {
                    $qq->orWhere('id', (int) $q);
                }
            });
        }

        $items = $query->orderBy('name')
            ->limit($q === '' ? 60 : 30)
            ->get($cols);

        $results = $items->map(function ($it) use ($imageCol) {
            $brand = '';
            if (Schema::hasColumn('catalog_items', 'brand_name')) {
                $brand = (string) ($it->brand_name ?? '');
            } elseif (Schema::hasColumn('catalog_items', 'brand')) {
                $brand = (string) ($it->brand ?? '');
            }

            return [
                'id'              => (int) $it->id,
                'name'            => (string) ($it->name ?? '—'),
                'sku'             => strtoupper((string) ($it->sku ?? '')),
                'barcode'         => Schema::hasColumn('catalog_items', 'barcode') ? strtoupper((string) ($it->barcode ?? '')) : '',
                'code'            => Schema::hasColumn('catalog_items', 'code') ? strtoupper((string) ($it->code ?? '')) : '',
                'brand'           => $brand,
                'image'           => $imageCol ? $this->imageUrl((string) ($it->{$imageCol} ?? '')) : '',
                'available_stock' => $this->stockDisponible((int) $it->id, $it),
                'location_code'   => $this->mejorUbicacion((int) $it->id, $it),
            ];
        })->all();

        return response()->json(['ok' => true, 'results' => $results]);
    }

    /** PASO 2: crea la tarea de picking real reutilizando el flujo del WMS (split físico/virtual). */
    public function crearPicking(Request $request, PropuestaResultado $resultado, WmsPickingController $wms)
    {
        $data = $request->validate([
            'assigned_user_id'        => ['required', 'integer', 'exists:users,id'],
            'order_number'            => ['nullable', 'string', 'max:120'],
            'priority'                => ['nullable', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'notes'                   => ['nullable', 'string', 'max:5000'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.product_id'      => ['required', 'integer', 'exists:catalog_items,id'],
            'items.*.product_name'    => ['nullable', 'string', 'max:255'],
            'items.*.product_sku'     => ['nullable', 'string', 'max:255'],
            'items.*.descripcion'     => ['nullable', 'string', 'max:5000'],
            'items.*.unidad'          => ['nullable', 'string', 'max:50'],
            'items.*.cantidad'        => ['required', 'integer', 'min:1'],
        ]);

        $folio = 'PROP-' . str_pad((string) $resultado->id, 6, '0', STR_PAD_LEFT);

        // Recalcula el stock real en el servidor (no confiamos en el del navegador).
        $items = [];
        foreach ($data['items'] as $row) {
            $productId = (int) $row['product_id'];
            $items[] = [
                'product_id'        => $productId,
                'product_name'      => $row['product_name'] ?? ($row['descripcion'] ?? 'Producto'),
                'product_sku'       => $row['product_sku'] ?? '',
                'description'       => $row['descripcion'] ?? '',
                'quantity_required' => (int) $row['cantidad'],
                'available_stock'   => $this->stockDisponible($productId),
                'location_code'     => $this->mejorUbicacion($productId),
                'delivery_phase'    => 1,
                'phase'             => 1,
            ];
        }

        try {
            $pickWave = $wms->createFromPayload([
                'assigned_user_id' => (int) $data['assigned_user_id'],
                'order_number'     => trim((string) ($data['order_number'] ?? '')) ?: $folio,
                'priority'         => $data['priority'] ?? 'normal',
                'notes'            => $data['notes'] ?? ('Surtido de propuesta ' . $folio),
                'total_phases'     => 1,
                'deliveries'       => [['phase' => 1, 'title' => 'Entrega 1']],
                'items'            => $items,
            ]);

            return response()->json([
                'ok'           => true,
                'pick_wave_id' => $pickWave->id,
                'redirect'     => route('admin.wms.picking.v2.edit', $pickWave),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'No se pudo crear la tarea.',
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Error al crear la tarea de picking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /** Partidas ganadas (texto libre) de la propuesta, listas para empatar. */
    private function partidasGanadas(PropuestaResultado $resultado): array
    {
        $resultado->loadMissing(['propuesta.items', 'items']);

        $saved = $resultado->items->keyBy('propuesta_comercial_item_id');
        $propuestaItems = optional($resultado->propuesta)->items
            ? $resultado->propuesta->items->sortBy('sort')->values()
            : collect();

        $rows = [];
        foreach ($propuestaItems as $index => $item) {
            $s = $saved->get($item->id);
            if ($s && $s->resultado === 'perdida') {
                continue;
            }

            $qty = (int) round((float) ($item->cantidad_cotizada ?: $item->cantidad_maxima ?: $item->cantidad_minima ?: 1));
            $unidad = $item->unidad_solicitada ?: 'Pieza';
            $descripcion = $item->descripcion_original ?: 'Producto';

            $rows[] = [
                'partida_id' => $item->id,
                'num'        => $item->partida_numero ?: ($index + 1),
                'desc'       => $descripcion,
                'unidad'     => $unidad,
                'cantidad'   => max(1, $qty),
            ];
        }

        return $rows;
    }

    /** Normaliza la URL de la imagen del producto. */
    private function imageUrl(?string $raw): string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }
        if (preg_match('#^(https?:)?//#', $raw) || str_starts_with($raw, '/')) {
            return $raw;
        }
        return asset($raw);
    }

    /** Stock LIBRE (inventario - reservado), con respaldo a catalog_items.stock. */
    private function stockDisponible(int $productId, $catalogItem = null): int
    {
        $invTable = (new Inventory())->getTable();
        $hasReserved = Schema::hasColumn($invTable, 'reserved_qty');

        $stock = (int) Inventory::query()
            ->where('catalog_item_id', $productId)
            ->get()
            ->sum(function ($row) use ($hasReserved) {
                $qty = (int) ($row->qty ?? 0);
                $reserved = $hasReserved ? (int) ($row->reserved_qty ?? 0) : 0;
                return max(0, $qty - $reserved);
            });

        if ($stock <= 0 && Schema::hasColumn('catalog_items', 'stock')) {
            if (!$catalogItem) {
                $catalogItem = CatalogItem::query()->find($productId, ['id', 'stock']);
            }
            $stock = max(0, (int) ($catalogItem->stock ?? 0));
        }

        return max(0, $stock);
    }

    /** Ubicación con más existencias, de referencia. */
    private function mejorUbicacion(int $productId, $catalogItem = null): string
    {
        $row = Inventory::query()
            ->with('location:id,code')
            ->where('catalog_item_id', $productId)
            ->get()
            ->sortByDesc(fn ($r) => (int) ($r->qty ?? 0))
            ->first();

        $code = strtoupper(trim((string) optional(optional($row)->location)->code));

        if ($code === '' && Schema::hasColumn('catalog_items', 'location_code')) {
            if (!$catalogItem) {
                $catalogItem = CatalogItem::query()->find($productId, Schema::hasColumn('catalog_items', 'location_code') ? ['id', 'location_code'] : ['id']);
            }
            $code = strtoupper(trim((string) ($catalogItem->location_code ?? '')));
        }

        return $code;
    }
}