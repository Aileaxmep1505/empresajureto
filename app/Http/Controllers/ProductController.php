<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductsImport;
use Illuminate\Support\Str;


class ProductController extends Controller
{
    /** Filtro de búsqueda reutilizable */
    private function applySearch($query, string $q)
    {
        $q = trim($q);
        if ($q === '') return $query;

        return $query->where(function($qq) use ($q){
            $qq->where('name', 'like', "%{$q}%")
               ->orWhere('sku', 'like', "%{$q}%")
               ->orWhere('brand', 'like', "%{$q}%")
               ->orWhere('category', 'like', "%{$q}%")
               ->orWhere('tags', 'like', "%{$q}%");
        });
    }

    /** Listado SIN paginación (todo de una) */
    public function index(Request $request)
    {
        $q = (string) $request->get('q', '');

        // ⚠️ Si tu base crece mucho, considera un límite razonable (p.ej. ->limit(2000))
        $products = $this->applySearch(Product::query(), $q)
            ->latest('id')
            ->get();

        return view('products.index-table', compact('products', 'q'));
    }

    /** Exportación a PDF */
    public function exportPdf(Request $request)
    {
        $q = (string) $request->get('q','');

        $items = $this->applySearch(Product::query(), $q)
            ->orderBy('name')
            ->get();

        $pdf = Pdf::loadView('products.pdf', [
            'items' => $items,
            'q'     => $q,
            'now'   => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('productos.pdf');
    }

    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    public function create()
    {
        return view('products.form', ['product' => new Product(), 'mode' => 'create']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => ['nullable','string','max:255'],
            'sku'             => ['nullable','string','max:255'],
            'supplier_sku'    => ['nullable','string','max:255'],
            'unit'            => ['nullable','string','max:100'],
            'weight'          => ['nullable','numeric'],
            'cost'            => ['nullable','numeric'],
            'price'           => ['nullable','numeric'],
            'market_price'    => ['nullable','numeric'],
            'bid_price'       => ['nullable','numeric'],
            'dimensions'      => ['nullable','string','max:255'],
            'color'           => ['nullable','string','max:255'],
            'pieces_per_unit' => ['nullable','integer','min:0'],
            'active'          => ['nullable','boolean'],
            'brand'           => ['nullable','string','max:255'],
            'category'        => ['nullable','string','max:255'],
            'material'        => ['nullable','string','max:255'],
            'description'     => ['nullable','string'],
            'notes'           => ['nullable','string'],
            'tags'            => ['nullable','string','max:255'],
            'image'           => ['nullable','image','max:4096'],
        ]);

        $data['active'] = (bool) $request->boolean('active');

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products','public');
        }

        $product = Product::create($data);

        return redirect()->route('products.edit',$product)
            ->with('status','Producto creado');
    }

    public function edit(Product $product)
    {
        return view('products.form', ['product' => $product, 'mode' => 'edit']);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'            => ['nullable','string','max:255'],
            'sku'             => ['nullable','string','max:255'],
            'supplier_sku'    => ['nullable','string','max:255'],
            'unit'            => ['nullable','string','max:100'],
            'weight'          => ['nullable','numeric'],
            'cost'            => ['nullable','numeric'],
            'price'           => ['nullable','numeric'],
            'market_price'    => ['nullable','numeric'],
            'bid_price'       => ['nullable','numeric'],
            'dimensions'      => ['nullable','string','max:255'],
            'color'           => ['nullable','string','max:255'],
            'pieces_per_unit' => ['nullable','integer','min:0'],
            'active'          => ['nullable','boolean'],
            'brand'           => ['nullable','string','max:255'],
            'category'        => ['nullable','string','max:255'],
            'material'        => ['nullable','string','max:255'],
            'description'     => ['nullable','string'],
            'notes'           => ['nullable','string'],
            'tags'            => ['nullable','string','max:255'],
            'image'           => ['nullable','image','max:4096'],
        ]);

        $data['active'] = (bool) $request->boolean('active');

        if ($request->hasFile('image')) {
            if ($product->image_path) Storage::disk('public')->delete($product->image_path);
            $data['image_path'] = $request->file('image')->store('products','public');
        }

        $product->update($data);

        return back()->with('status','Producto actualizado');
    }

    public function destroy(Product $product)
    {
        if ($product->image_path) Storage::disk('public')->delete($product->image_path);
        $product->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok'=>true]);
        }
        return redirect()->route('products.index')->with('status','Producto eliminado');
    }

    public function importForm()
    {
        return view('products.import');
    }

public function importStore(Request $request)
{
    $data = $request->validate([
        'file'            => ['required','file','mimes:xlsx,xls,csv','max:51200'],
        'download_images' => ['nullable','boolean'],
        'queue'           => ['nullable','boolean'],
    ]);

    try {
        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        // Comprueba BD actual y conteo previo
        $dbName = \DB::connection()->getDatabaseName();
        $before = \App\Models\Product::count();
        \Log::info('Import inicia', ['db' => $dbName, 'before_count' => $before, 'ext' => $ext]);

        if (in_array($ext, ['xlsx','xls'], true) && !class_exists(\ZipArchive::class)) {
            return back()->withErrors(['file' => 'Habilita ZipArchive en php.ini (o importa como CSV).']);
        }

        $import = new \App\Imports\ProductsImport(
            downloadImages: (bool)$request->boolean('download_images')
        );

        \Maatwebsite\Excel\Facades\Excel::import($import, $file);

        $after = \App\Models\Product::count();
        \Log::info('Import termina', [
            'db'       => $dbName,
            'after_count' => $after,
            'created'  => $import->created,
            'updated'  => $import->updated,
            'skipped'  => $import->skipped,
        ]);

        $failures = method_exists($import, 'failures') ? $import->failures() : [];
        $msg = "Importación completada. Nuevos: {$import->created}, Actualizados: {$import->updated}, Omitidos: {$import->skipped}. Total antes: {$before}, después: {$after} (BD: {$dbName}).";

        return back()->with(['status' => $msg, 'failures' => $failures]);
    } catch (\Throwable $e) {
        \Log::error('Importación de productos fallida', [
            'msg'   => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return back()->withErrors(['file' => 'Error al importar: '.$e->getMessage()]);
    }
}


    private function importCsv(string $path): int
    {
        if (!is_readable($path)) throw new \RuntimeException('No se pudo leer el CSV.');
        $h = fopen($path, 'r'); if ($h === false) throw new \RuntimeException('No se pudo abrir el CSV.');

        $header = null; $count = 0;
        while (($row = fgetcsv($h, 0, ',')) !== false) {
            if ($header === null) {
                $header = array_map(function($s){
                    $s = strtolower(trim($s ?? ''));
                    $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u']);
                    $s = preg_replace('/[^a-z0-9]+/i', '_', $s);
                    return trim($s, '_');
                }, $row);
                continue;
            }

            $assoc = [];
            foreach ($header as $i => $k) $assoc[$k] = $row[$i] ?? null;

            $bool = fn($v) => in_array(strtolower((string)$v), ['1','true','si','sí','yes','y'], true);

            $data = [
                'name'            => $assoc['name']            ?? ($assoc['nombre'] ?? null),
                'sku'             => $assoc['sku']             ?? null,
                'supplier_sku'    => $assoc['supplier_sku']    ?? ($assoc['sku_prov'] ?? null),
                'unit'            => $assoc['unit']            ?? ($assoc['unidad'] ?? null),
                'weight'          => $assoc['weight']          ?? null,
                'cost'            => $assoc['cost']            ?? null,
                'price'           => $assoc['price']           ?? null,
                'market_price'    => $assoc['market_price']    ?? null,
                'bid_price'       => $assoc['bid_price']       ?? null,
                'dimensions'      => $assoc['dimensions']      ?? ($assoc['dimensiones'] ?? null),
                'color'           => $assoc['color']           ?? null,
                'pieces_per_unit' => $assoc['pieces_per_unit'] ?? ($assoc['pzs_u'] ?? null),
                'active'          => $bool($assoc['active'] ?? ($assoc['activo'] ?? '1')),
                'brand'           => $assoc['brand']           ?? ($assoc['marca'] ?? null),
                'category'        => $assoc['category']        ?? ($assoc['categoria'] ?? null),
                'material'        => $assoc['material']        ?? null,
                'description'     => $assoc['description']     ?? ($assoc['descripcion'] ?? null),
                'notes'           => $assoc['notes']           ?? ($assoc['notas'] ?? null),
                'tags'            => $assoc['tags']            ?? null,
            ];

            if (!empty($data['sku'])) {
                Product::updateOrCreate(['sku' => $data['sku']], $data);
            } else {
                Product::create($data);
            }
            $count++;
        }

        fclose($h);
        return $count;
    }
      /** API: listado paginado en JSON (para el frontend React) */
  public function apiIndex(Request $request)
{
    $q        = (string) $request->get('q', '');
    $category = $request->get('category');
    $sort     = $request->get('sort');
    $min      = $request->get('min_price');
    $max      = $request->get('max_price');
    $per      = (int) $request->get('per_page', 24);

    $query = $this->applySearch(Product::query(), $q);

    if (!empty($category)) $query->where('category', $category);
    if ($min !== null && $min !== '') $query->where(function($qq) use ($min){
        $qq->where('price','>=',(float)$min)
           ->orWhere('market_price','>=',(float)$min)
           ->orWhere('bid_price','>=',(float)$min);
    });
    if ($max !== null && $max !== '') $query->where(function($qq) use ($max){
        $qq->where('price','<=',(float)$max)
           ->orWhere('market_price','<=',(float)$max)
           ->orWhere('bid_price','<=',(float)$max);
    });

    // 🔽 1) Ordena para que "con nombre y con precio" aparezcan primero
    //    (sin romper tu sort elegido).
    $query->orderByRaw("
        (CASE WHEN (name IS NULL OR name='') THEN 1 ELSE 0 END) ASC,
        (CASE WHEN COALESCE(price, market_price, bid_price, 0) = 0 THEN 1 ELSE 0 END) ASC
    ");

    switch ($sort) {
        case 'newest':     $query->orderByDesc('id'); break;
        case 'price_asc':  $query->orderByRaw('COALESCE(price, market_price, bid_price, 0) ASC'); break;
        case 'price_desc': $query->orderByRaw('COALESCE(price, market_price, bid_price, 0) DESC'); break;
        default:           $query->orderByDesc('id'); break;
    }

    $page = $query->paginate($per)->through(function (\App\Models\Product $p) {
        // 🔽 2) Coalesce de precio (valor crudo, numérico)
        $priceRaw = $p->getRawOriginal('price');
        if ($priceRaw === null || (float)$priceRaw == 0.0) {
            $priceRaw = $p->getRawOriginal('market_price');
        }
        if ($priceRaw === null || (float)$priceRaw == 0.0) {
            $priceRaw = $p->getRawOriginal('bid_price');
        }

        $listRaw  = $p->getRawOriginal('market_price');

        // 🔽 3) Nombre/marca con fallback seguro
        $name  = (string)($p->name ?? $p->getRawOriginal('name') ?? '');
        $brand = (string)($p->brand ?? $p->getRawOriginal('brand') ?? '');

        // 🔽 4) Imagen: si accessor no resuelve, manda placeholder absoluto
        $img = $p->image_src ?: url('/placeholder.png');

        return [
            'id'         => $p->id,
            'slug'       => $p->slug ?? (string)$p->id,
            'name'       => ($name !== '' ? $name : 'Producto'),
            'sku'        => $p->sku,
            'brand'      => $brand,
            'category'   => $p->category,
            'price'      => $priceRaw !== null ? (float)$priceRaw : 0.0,
            'list_price' => $listRaw  !== null ? (float)$listRaw  : null,
            'short_description' => $p->description ? Str::limit(strip_tags($p->description), 140) : null,
            'image_src'  => $img,
            'active'     => (bool)$p->active,

            // Extras UI
            'rating'        => (float)($p->rating ?? 0),
            'reviews_count' => (int)($p->reviews_count ?? 0),
            'free_shipping' => (bool)($p->free_shipping ?? false),
            'badge'         => $p->badge ?? null,
        ];
    });

    // Log de verificación (verás ahora precio>0, brand/name reales en los primeros)
    if ($page->count()) \Log::info('apiIndex sample', ['first' => $page->items()[0]]);

    return response()->json($page);
}
    /** GET /api/products/{product} -> detalle JSON */
    public function apiShow(Product $product)
    {
        return response()->json([
            'id'          => $product->id,
            'name'        => $product->name,
            'sku'         => $product->sku,
            'brand'       => $product->brand,
            'category'    => $product->category,
            'price'       => $product->price,
            'list_price'  => $product->market_price ?? null,
            'description' => $product->description,
            'image_src'   => $product->image_src,
            'active'      => (bool)$product->active,
        ]);
    }
}
