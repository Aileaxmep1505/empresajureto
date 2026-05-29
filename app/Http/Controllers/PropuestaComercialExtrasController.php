<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\PropuestaComercialItem;
use App\Models\TechSheet;
use App\Services\TechSheetAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PropuestaComercialExtrasController extends Controller
{
    /* ======================================================
     |  MUESTRAS  →  análisis de almacén desde catalog_items
     ======================================================*/
    public function itemSamples(PropuestaComercialItem $item)
    {
        $descripcion = (string) $item->descripcion_original;

        $needed = (float) ($item->cantidad_cotizada
            ?: $item->cantidad_maxima
            ?: $item->cantidad_minima
            ?: 1);

        $candidates = $this->searchCatalogCandidates($descripcion, 8)
            ->map(function (CatalogItem $ci) use ($needed) {
                $stock = $this->stockInfo($ci);
                $toBuy = max(0, (int) ceil($needed - $stock['net_available']));

                return [
                    'id'             => $ci->id,
                    'name'           => $ci->name,
                    'sku'            => $ci->sku,
                    'unit'           => $ci->unit_measure,
                    'price'          => (float) $ci->price,
                    'similarity_pct' => $ci->__similarity ?? 0,
                    'stock_field'    => $stock['stock_field'],
                    'available'      => $stock['available'],
                    'reserved'       => $stock['reserved'],
                    'net_available'  => $stock['net_available'],
                    'to_buy'         => $toBuy,
                    'locations'      => $stock['locations'],
                ];
            })
            ->values();

        return response()->json([
            'ok'         => true,
            'item_id'    => $item->id,
            'needed_qty' => $needed,
            'candidates' => $candidates,
        ]);
    }

    protected function searchCatalogCandidates(string $descripcion, int $limit = 8)
    {
        $desc = trim($descripcion);

        if ($desc === '') {
            return collect();
        }

        $cols = collect(['name', 'sku', 'description', 'excerpt', 'brand_name', 'model_name'])
            ->filter(fn ($c) => Schema::hasColumn('catalog_items', $c))
            ->values();

        if ($cols->isEmpty()) {
            return collect();
        }

        $words = collect(preg_split('/\s+/', mb_strtolower($desc)))
            ->map(fn ($w) => trim($w))
            ->filter(fn ($w) => mb_strlen($w) >= 3 && !is_numeric($w))
            ->unique()
            ->take(8)
            ->values();

        $rows = CatalogItem::query()
            ->with(['inventoryRows.location'])
            ->where(function ($q) use ($words, $cols, $desc) {
                foreach ($words as $w) {
                    foreach ($cols as $c) {
                        $q->orWhere($c, 'like', "%{$w}%");
                    }
                }
                foreach ($cols as $c) {
                    $q->orWhere($c, 'like', "%{$desc}%");
                }
            })
            ->limit(120)
            ->get();

        $descNorm = mb_strtolower($desc);

        return $rows
            ->map(function (CatalogItem $ci) use ($descNorm) {
                $hay = mb_strtolower(trim(implode(' ', array_filter([
                    $ci->name, $ci->sku, $ci->brand_name, $ci->model_name, $ci->description,
                ]))));

                $pct = 0;
                similar_text($descNorm, $hay, $pct);
                $ci->__similarity = round($pct, 1);

                return $ci;
            })
            ->sortByDesc('__similarity')
            ->take($limit)
            ->values();
    }

    protected function stockInfo(CatalogItem $ci): array
    {
        $rows = $ci->inventoryRows;

        $available = 0;
        $reserved  = 0;
        $locations = [];

        if ($rows && $rows->count()) {
            foreach ($rows as $r) {
                $qty = (int) $r->qty;
                $res = (int) $r->reserved_qty;

                $available += $qty;
                $reserved  += $res;

                $locations[] = [
                    'location' => optional($r->location)->name
                        ?: optional($r->location)->code
                        ?: ('Ubicación #' . $r->location_id),
                    'qty'      => $qty,
                    'reserved' => $res,
                ];
            }
        } else {
            $available = (int) $ci->stock;
        }

        return [
            'stock_field'   => (int) $ci->stock,
            'available'     => $available,
            'reserved'      => $reserved,
            'net_available' => max($available - $reserved, 0),
            'locations'     => $locations,
        ];
    }

    /* ======================================================
     |  FICHAS TÉCNICAS
     ======================================================*/
    public function techSheetsList(Request $request, PropuestaComercialItem $item)
    {
        $q = trim((string) $request->get('q', ''));

        $sheets = TechSheet::query()
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($qq) use ($q) {
                    $qq->where('product_name', 'like', "%{$q}%")
                       ->orWhere('brand', 'like', "%{$q}%")
                       ->orWhere('model', 'like', "%{$q}%")
                       ->orWhere('reference', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return response()->json([
            'ok'        => true,
            'linked_id' => data_get($item->meta, 'tech_sheet_id'),
            'sheets'    => $sheets->map(fn ($s) => $this->serializeSheet($s))->values(),
        ]);
    }

    public function linkTechSheet(Request $request, PropuestaComercialItem $item)
    {
        $data = $request->validate([
            'tech_sheet_id' => ['nullable', 'integer', 'exists:tech_sheets,id'],
        ]);

        $meta = is_array($item->meta) ? $item->meta : [];

        if (empty($data['tech_sheet_id'])) {
            unset($meta['tech_sheet_id'], $meta['tech_sheet_name']);
        } else {
            $sheet = TechSheet::find($data['tech_sheet_id']);
            $meta['tech_sheet_id']   = $sheet->id;
            $meta['tech_sheet_name'] = $sheet->product_name;
        }

        $item->update(['meta' => $meta]);

        return response()->json([
            'ok'        => true,
            'linked_id' => data_get($item->meta, 'tech_sheet_id'),
        ]);
    }

    public function createTechSheet(Request $request, PropuestaComercialItem $item, TechSheetAiService $ai)
    {
        $data = $request->validate([
            'product_name'     => ['required', 'string', 'max:255'],
            'user_description' => ['nullable', 'string'],
            'brand'            => ['nullable', 'string', 'max:255'],
            'model'            => ['nullable', 'string', 'max:255'],
            'reference'        => ['nullable', 'string', 'max:255'],
            'partida_number'   => ['nullable', 'string', 'max:50'],
            'image'            => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('tech_sheets', 'public');
        }
        unset($data['image']);

        $aiData = [];
        try {
            $aiData = $ai->generate($data) ?? [];
        } catch (\Throwable $e) {
            $aiData = [];
        }

        $sheet = TechSheet::create(array_merge($data, $aiData, [
            'public_token' => (string) Str::uuid(),
        ]));

        $meta = is_array($item->meta) ? $item->meta : [];
        $meta['tech_sheet_id']   = $sheet->id;
        $meta['tech_sheet_name'] = $sheet->product_name;
        $item->update(['meta' => $meta]);

        return response()->json([
            'ok'    => true,
            'sheet' => $this->serializeSheet($sheet),
        ]);
    }

    public function updateTechSheet(Request $request, TechSheet $sheet)
    {
        $data = $request->validate([
            'product_name'     => ['required', 'string', 'max:255'],
            'user_description' => ['nullable', 'string'],
            'brand'            => ['nullable', 'string', 'max:255'],
            'model'            => ['nullable', 'string', 'max:255'],
            'reference'        => ['nullable', 'string', 'max:255'],
            'partida_number'   => ['nullable', 'string', 'max:50'],
            'image'            => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('tech_sheets', 'public');
        }
        unset($data['image']);

        if (empty($sheet->public_token)) {
            $data['public_token'] = (string) Str::uuid();
        }

        $sheet->update($data);

        return response()->json([
            'ok'    => true,
            'sheet' => $this->serializeSheet($sheet),
        ]);
    }

    protected function serializeSheet(TechSheet $s): array
    {
        return [
            'id'             => $s->id,
            'product_name'   => $s->product_name,
            'brand'          => $s->brand,
            'model'          => $s->model,
            'reference'      => $s->reference,
            'partida_number' => $s->partida_number,
            'urls'           => [
                'show'   => route('tech-sheets.show', $s),
                'edit'   => route('tech-sheets.edit', $s),
                'pdf'    => route('tech-sheets.pdf', $s),
                'public' => $s->public_token ? route('tech-sheets.public', $s->public_token) : null,
            ],
        ];
    }
}