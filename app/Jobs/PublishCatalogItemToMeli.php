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
use Illuminate\Support\Facades\Log;

class PublishCatalogItemToMeli implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $catalogItemId,
        public array $options = []
    ) {}

    public function handle(MeliSyncService $meli)
    {
        $item = CatalogItem::withTrashed()->find($this->catalogItemId);
        if (!$item) return;

        // Si se pidió pausar explícitamente
        if (!empty($this->options['pause']) && !empty($item->meli_item_id)) {
            $meli->pause($item);
            return;
        }

        // Si está borrador/oculto y existe en ML => pausar
        if ((int)$item->status !== 1 && !empty($item->meli_item_id)) {
            $meli->pause($item);
            return;
        }

        // Si está publicado => crear/actualizar
        if ((int)$item->status === 1) {
            $opts = [
                'activate'           => !empty($this->options['activate']),
                'ensure_picture'     => !empty($this->options['ensure_picture']),
                'update_description' => !empty($this->options['update_description']),
            ];
            $meli->sync($item, $opts);
            return;
        }

        // Si está borrador y no existe en ML => no hacer nada
        Log::info('PublishCatalogItemToMeli: item no publicado, sin acción ML', [
            'id' => $item->id, 'status' => $item->status
        ]);
    }
}
