<?php
// app/Jobs/PublishCatalogItemToMeli.php
namespace App\Jobs;

use App\Models\CatalogItem;
use App\Services\MeliSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishCatalogItemToMeli implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $catalogItemId) {}

    public function handle(MeliSyncService $svc): void
    {
        $item = CatalogItem::find($this->catalogItemId);
        if (!$item) return;

        // Solo sincroniza si está publicado (status=1)
        if ((int)$item->status !== 1) {
            // Si no está publicado y existe en ML → pausar
            if ($item->meli_item_id) $svc->pause($item);
            return;
        }

        $svc->sync($item);
    }
}
