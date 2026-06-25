<?php

namespace App\Console\Commands;

use App\Services\ShopifyService;
use Illuminate\Console\Command;

class ShopifyListLocations extends Command
{
    protected $signature = 'shopify:locations';

    protected $description = 'Lista las ubicaciones disponibles de Shopify';

    public function handle(ShopifyService $shopify): int
    {
        $query = <<<'GRAPHQL'
query {
  locations(first: 20) {
    edges {
      node {
        id
        name
        isActive
        address {
          address1
          city
          province
          country
          zip
        }
      }
    }
  }
}
GRAPHQL;

        try {
            $result = $shopify->graphql($query);

            $locations = $result['data']['locations']['edges'] ?? [];

            if (empty($locations)) {
                $this->warn('No se encontraron locations en Shopify.');
                return self::SUCCESS;
            }

            foreach ($locations as $edge) {
                $location = $edge['node'];

                $this->line('');
                $this->info($location['name']);
                $this->line('ID: ' . $location['id']);
                $this->line('Activa: ' . ($location['isActive'] ? 'Sí' : 'No'));

                $address = $location['address'] ?? [];

                $this->line('Dirección: ' . trim(
                    ($address['address1'] ?? '') . ' ' .
                    ($address['city'] ?? '') . ' ' .
                    ($address['province'] ?? '') . ' ' .
                    ($address['country'] ?? '') . ' ' .
                    ($address['zip'] ?? '')
                ));
            }

            $this->line('');
            $this->comment('Copia el ID de la location activa y pégalo en SHOPIFY_LOCATION_ID.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}