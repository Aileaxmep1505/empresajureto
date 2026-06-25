<?php

namespace App\Console\Commands;

use App\Models\CatalogItem;
use App\Services\ShopifyService;
use Illuminate\Console\Command;

class SyncCatalogItemsToShopify extends Command
{
    protected $signature = 'shopify:sync-catalog {--id=}';

    protected $description = 'Sincroniza productos del catálogo local hacia Shopify';

    public function handle(ShopifyService $shopify): int
    {
        $query = CatalogItem::query()
            ->where('is_sample', false)
            ->whereNotNull('sku');

        if ($this->option('id')) {
            $query->where('id', $this->option('id'));
        }

        $query->chunk(50, function ($items) use ($shopify) {
            foreach ($items as $item) {
                try {
                    $this->info("Sincronizando: {$item->id} - {$item->name}");
                    $shopify->syncCatalogItem($item);
                    $this->info("OK");
                } catch (\Throwable $e) {
                    $this->error("Error {$item->id}: " . $e->getMessage());
                }
            }
        });

        return self::SUCCESS;
    }
}