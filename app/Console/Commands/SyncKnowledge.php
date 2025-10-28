<?php
// app/Console/Commands/SyncKnowledge.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\KnowledgeDocument;
use App\Services\Embeddings;

class SyncKnowledge extends Command
{
  protected $signature = 'knowledge:sync {--rebuild}';
  protected $description = 'Sincroniza/embebe conocimiento desde BD y config/knowledge.php';

  public function handle(): int
  {
    if ($this->option('rebuild')) {
      KnowledgeDocument::truncate();
      $this->warn('knowledge_documents truncada.');
    }

    $count = 0;
    $count += $this->ingestProducts();
    $count += $this->ingestFaqs();
    $count += $this->ingestPages();
    $count += $this->ingestPolicies();
    $count += $this->ingestStaticFromConfig();

    $this->info("Documentos procesados: {$count}");
    return self::SUCCESS;
  }

  protected function chunkAndStore(string $sourceType, string $baseId, string $title, ?string $url, string $text, array $meta = []): int
  {
    $text = trim(strip_tags($text));
    if ($text==='') return 0;

    $chunks = $this->chunk($text, 1600, 220);
    $i=0; $total=0;
    foreach ($chunks as $chunk) {
      $sourceId = $baseId.($i?("#".$i):"");
      $doc = KnowledgeDocument::updateOrCreate(
        ['source_type'=>$sourceType,'source_id'=>$sourceId],
        [
          'title'=>$title, 'url'=>$url, 'content'=>$chunk,
          'meta'=>$meta, 'is_active'=>true,
        ]
      );
      $embed = Embeddings::embed($chunk);
      if ($embed) {
        $doc->embedding = json_encode($embed);
        $doc->save();
      }
      $i++; $total++;
    }
    return $total;
  }

  protected function chunk(string $text, int $maxChars=1600, int $overlap=220): array
  {
    $text = preg_replace("/\n{3,}/", "\n\n", $text);
    $len  = mb_strlen($text);
    if ($len <= $maxChars) return [$text];

    $out=[]; $start=0;
    while ($start < $len) {
      $end = min($start + $maxChars, $len);
      $slice = mb_substr($text, $start, $end-$start);
      $out[] = $slice;
      if ($end >= $len) break;
      $start = $end - $overlap; if ($start<0) $start=0;
    }
    return $out;
  }

  protected function ingestProducts(): int
  {
    if (!Schema::hasTable('catalog_items') && !Schema::hasTable('products')) return 0;

    $table = Schema::hasTable('catalog_items') ? 'catalog_items' : 'products';
    $hasDesc  = Schema::hasColumn($table,'description') || Schema::hasColumn($table,'descripcion');
    $hasTitle = Schema::hasColumn($table,'name') || Schema::hasColumn($table,'titulo') || Schema::hasColumn($table,'title');

    $rows = DB::table($table)->whereNull('deleted_at')->orWhereNull('deleted_at')->limit(5000)->get();
    $n=0;
    foreach ($rows as $r) {
      $id    = (string)($r->id ?? $r->sku ?? Str::uuid());
      $title = $r->name ?? $r->title ?? $r->titulo ?? ('Producto #'.$id);
      $desc  = $r->description ?? $r->descripcion ?? '';
      $brand = $r->brand ?? $r->marca ?? null;
      $specs = $r->specs ?? $r->especificaciones ?? null;

      $text = $title."\n".
              ($brand ? "Marca: {$brand}\n" : "").
              ($desc ? $desc."\n" : "").
              ($specs ? strip_tags($specs)."\n" : "");

      $url = url('/producto/'.$id); // ajusta a tu ruta real
      $n += $this->chunkAndStore('products', $id, $title, $url, $text, ['table'=>$table]);
    }
    $this->info("Products: {$n}");
    return $n;
  }

  protected function ingestFaqs(): int
  {
    if (!Schema::hasTable('faqs')) return 0;
    $rows = DB::table('faqs')->where('activo',1)->orWhereNull('activo')->limit(5000)->get();
    $n=0;
    foreach ($rows as $r) {
      $id = (string)($r->id ?? Str::uuid());
      $q  = $r->question ?? $r->pregunta ?? 'FAQ';
      $a  = $r->answer   ?? $r->respuesta ?? '';
      $text = "Pregunta: {$q}\nRespuesta: ".strip_tags($a);
      $n += $this->chunkAndStore('faqs', $id, $q, null, $text);
    }
    $this->info("FAQs: {$n}");
    return $n;
  }

  protected function ingestPages(): int
  {
    if (!Schema::hasTable('pages')) return 0;
    $rows = DB::table('pages')->where('is_active',1)->orWhereNull('is_active')->limit(2000)->get();
    $n=0;
    foreach ($rows as $r) {
      $slug  = (string)($r->slug ?? Str::slug($r->title ?? 'page-'.($r->id ?? Str::uuid())));
      $title = $r->title ?? 'Página';
      $body  = $r->body  ?? $r->content ?? '';
      $url   = url('/'.$slug);
      $text  = strip_tags($title."\n".$body);
      $n += $this->chunkAndStore('pages', $slug, $title, $url, $text);
    }
    $this->info("Pages: {$n}");
    return $n;
  }

  protected function ingestPolicies(): int
  {
    if (!Schema::hasTable('policies')) return 0;
    $rows = DB::table('policies')->where('is_active',1)->orWhereNull('is_active')->get();
    $n=0;
    foreach ($rows as $r) {
      $slug  = (string)($r->slug ?? 'policy-'.($r->id ?? Str::uuid()));
      $title = $r->title ?? 'Política';
      $body  = $r->body ?? '';
      $url   = url('/politicas/'.$slug);
      $text  = strip_tags($title."\n".$body);
      $n += $this->chunkAndStore('policies', $slug, $title, $url, $text);
    }
    $this->info("Policies: {$n}");
    return $n;
  }

  protected function ingestStaticFromConfig(): int
  {
    $cfg = config('knowledge.static', []);
    $n=0;
    foreach ($cfg as $doc) {
      $slug  = $doc['slug'] ?? Str::slug($doc['title'] ?? Str::uuid());
      $title = $doc['title'] ?? 'Documento';
      $url   = $doc['url']   ?? null;
      $text  = $doc['content'] ?? '';
      $n += $this->chunkAndStore('static', $slug, $title, $url, $text, ['source'=>'config']);
    }
    $this->info("Static config: {$n}");
    return $n;
  }
}
