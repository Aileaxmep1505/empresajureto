<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SyncKnowledge extends Command
{
    protected $signature = 'knowledge:sync {--rebuild : Vacía y reconstruye toda la tabla}';
    protected $description = 'Indexa conocimiento desde productos, config estática y BLADES (web/politicas)';

    public function handle(): int
    {
        $rebuild = (bool)$this->option('rebuild');

        if ($rebuild) {
            DB::table('knowledge_documents')->truncate();
            $this->line('knowledge_documents truncada.');
        }

        $total = 0;
        $total += $this->indexProducts();
        $total += $this->indexStaticConfig();
        $total += $this->indexPolicyBlades();

        $this->info("Documentos procesados: {$total}");
        return self::SUCCESS;
    }

    /* =========================
       1) Productos del catálogo
       ========================= */
    protected function indexProducts(): int
    {
        $count = 0;

        if (!class_exists(\App\Models\CatalogItem::class)) {
            $this->warn('Catálogo: modelo App\\Models\\CatalogItem no existe, se omite.');
            $this->line('Products: 0');
            return 0;
        }

        $items = \App\Models\CatalogItem::query()->limit(5000)->get();
        foreach ($items as $it) {
            $title = $it->name ?? $it->titulo ?? $it->title ?? ('Item '.$it->id);
            $brand = $it->brand ?? $it->marca ?? null;
            $sku   = $it->sku ?? null;
            $desc  = $it->description ?? $it->descripcion ?? null;

            $content = implode("\n", array_filter([
                (string)$title,
                $brand ? "Marca: {$brand}" : null,
                $sku ? "SKU: {$sku}" : null,
                $desc,
            ]));

            $slug = $it->slug ?? $it->id;
            $url  = url('/catalogo/'.$slug);

            $this->upsertDoc('products', (string)$it->id, $title, $url, $content, [
                'table' => 'catalog_items',
            ]);
            $count++;
        }

        $this->line("Products: {$count}");
        return $count;
    }

    /* ===========================================
       2) Config estática (config/knowledge.php)
       =========================================== */
    protected function indexStaticConfig(): int
    {
        $count = 0;
        $cfg = config('knowledge.static', []);
        if (!is_array($cfg) || empty($cfg)) {
            $this->line('Static config: 0');
            return 0;
        }

        foreach ($cfg as $row) {
            $slug    = (string)($row['slug'] ?? Str::slug($row['title'] ?? 'static'));
            $title   = (string)($row['title'] ?? Str::title(str_replace('-', ' ', $slug)));
            $url     = (string)($row['url'] ?? url('/'.$slug));
            $content = (string)($row['content'] ?? '');

            $this->upsertDoc('static', $slug, $title, $url, $content, ['source' => 'config']);
            $count++;
        }

        $this->line("Static config: {$count}");
        return $count;
    }

    /* =====================================================
       3) HTML/Blade: resources/views/web/politicas/*.blade.php
       ===================================================== */
    protected function indexPolicyBlades(): int
    {
        $dir = resource_path('views/web/politicas');
        if (!File::exists($dir)) {
            $this->line('Blades (politicas): 0 (no existe la carpeta)');
            return 0;
        }

        // Mapeo slug => [routeName, fallbackUrl]
        $routeMap = [
            'terminos'          => ['policy.terms',              '/terminos-y-condiciones'],
            'garantias'         => ['policy.returns',            '/garantias-y-devoluciones'],
            'envios'            => ['policy.shipping',           '/envios-devoluciones-cancelaciones'],
            'envios-skydropx'   => ['policy.shipping.methods',   '/formas-de-envio'],
            'privacidad'        => ['policy.privacy',            '/aviso-de-privacidad'],
            'faq'               => ['policy.faq',                '/preguntas-frecuentes'],
        ];

        $files = collect(File::allFiles($dir))
            ->filter(fn($f)=>Str::endsWith($f->getFilename(), '.blade.php'))
            ->values();

        $count = 0;
        foreach ($files as $file) {
            $path = $file->getRealPath();
            $blade = File::get($path) ?? '';
            $filename = $file->getFilename(); // p.ej. terminos.blade.php
            $slug = Str::of($filename)->replace('.blade.php', '')->lower()->value();

            // Extrae @section('content') si existe; si no, toma todo
            $html = $this->extractSection($blade, 'content') ?? $blade;

            // Título desde @section('title','...') o <h1> ... </h1>
            $title = $this->extractTitle($blade, $html) ?: Str::title(str_replace('-', ' ', $slug));

            // URL desde ruta si existe, si no fallback “bonito”
            [$routeName, $fallback] = $routeMap[$slug] ?? [null, '/'.$slug];
            $url = $this->routeOrFallback($routeName, $fallback);

            // Convierte el HTML a texto plano legible
            $text = $this->htmlToText($html);

            $this->upsertDoc('pages', (string)$slug, $title, $url, $text, [
                'view_file' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $path),
            ]);

            $count++;
        }

        $this->line("Blades (politicas): {$count}");
        return $count;
    }

    /* =======================
       Helpers de extracción
       ======================= */

    protected function extractSection(string $blade, string $sectionName): ?string
    {
        // Busca @section('content') ... @endsection
        $regex = "/@section\\(['\"]{$sectionName}['\"]\\)(.*?)@endsection/su";
        if (preg_match($regex, $blade, $m)) {
            return $m[1] ?? null;
        }
        return null;
    }

    protected function extractTitle(string $blade, string $htmlFallback): ?string
    {
        // 1) @section('title','Mi Título')
        if (preg_match("/@section\\('title'\\s*,\\s*'([^']+)'\\)/u", $blade, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/@section\\("title"\\s*,\\s*"([^"]+)"\\)/u', $blade, $m2)) {
            return trim($m2[1]);
        }
        // 2) <h1> ... </h1>
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/isu', $htmlFallback, $m3)) {
            return trim(strip_tags($m3[1]));
        }
        return null;
        }

    protected function htmlToText(string $html): string
    {
        // Quita <script>/<style>
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html);

        // Inserta saltos antes de eliminar tags
        $html = str_ireplace(
            ['</p>','</div>','</section>','</li>','</h1>','</h2>','</h3>','</tr>','<br>','<br/>','<br />'],
            ["</p>\n","</div>\n","</section>\n","</li>\n","</h1>\n","</h2>\n","</h3>\n","</tr>\n","\n","\n","\n"],
            $html
        );

        // Convierte <li> en bullets
        $html = preg_replace('#<li[^>]*>\s*#i', "• ", $html);

        // Quita el resto de tags
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normaliza espacios y saltos
        $text = preg_replace("/[ \t]+/u", ' ', $text);
        $text = preg_replace("/\n{3,}/u", "\n\n", $text);

        return trim($text);
    }

    protected function routeOrFallback(?string $routeName, string $fallback): string
    {
        try {
            if ($routeName && \Route::has($routeName)) {
                return route($routeName);
            }
        } catch (\Throwable $e) { /* ignore */ }

        return url($fallback);
    }

    protected function upsertDoc(string $sourceType, string $sourceId, string $title, string $url, string $content, array $meta = []): void
    {
        $now = now();
        $row = DB::table('knowledge_documents')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();

        $payload = [
            'source_type' => $sourceType,
            'source_id'   => $sourceId,
            'title'       => $title,
            'url'         => $url,
            'content'     => $content,
            'meta'        => json_encode($meta, JSON_UNESCAPED_UNICODE),
            'is_active'   => true,
            'updated_at'  => $now,
        ];

        if ($row) {
            DB::table('knowledge_documents')
              ->where('id', $row->id)
              ->update($payload);
        } else {
            $payload['created_at'] = $now;
            DB::table('knowledge_documents')->insert($payload);
        }
    }
}
