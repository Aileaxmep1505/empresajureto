<?php
// app/Services/Embeddings.php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class Embeddings
{
  public static function embed(string $text): array
  {
    $key = config('services.openai.key');
    $model = config('services.openai.embed_model', 'text-embedding-3-small');

    if (!$key) return []; // sin clave: sin embedding (seguirá funcionando con keyword)

    $resp = Http::withHeaders([
      'Authorization'=>"Bearer {$key}",
      'Content-Type'=>'application/json',
    ])->post('https://api.openai.com/v1/embeddings', [
      'model' => $model,
      'input' => mb_substr($text, 0, 6000), // recorte prudente
    ]);

    return data_get($resp->json(),'data.0.embedding',[]) ?: [];
  }

  public static function cosine(array $a, array $b): float
  {
    if (count($a) !== count($b) || empty($a)) return 0.0;
    $dot=$na=0.0; $nb=0.0;
    $n=count($a);
    for($i=0;$i<$n;$i++){
      $dot += $a[$i]*$b[$i];
      $na  += $a[$i]*$a[$i];
      $nb  += $b[$i]*$b[$i];
    }
    if ($na==0 || $nb==0) return 0.0;
    return $dot / (sqrt($na)*sqrt($nb));
  }
}
