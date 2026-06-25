<?php

namespace App\Services;

use App\Models\CatalogItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShopifyService
{
    protected string $shop;
    protected string $token;
    protected string $version;
    protected string $endpoint;

    public function __construct()
    {
        $this->shop = (string) config('services.shopify.shop');
        $this->token = (string) config('services.shopify.token');
        $this->version = (string) config('services.shopify.version', '2026-04');

        $this->endpoint = "https://{$this->shop}/admin/api/{$this->version}/graphql.json";
    }

    public function graphql(string $query, array $variables = []): array
    {
        if (!$this->shop) {
            throw new \Exception('Falta configurar SHOPIFY_SHOP.');
        }

        if (!$this->token) {
            throw new \Exception('Falta configurar SHOPIFY_ADMIN_TOKEN.');
        }

        $payload = [
            'query' => $query,
        ];

        if (!empty($variables)) {
            $payload['variables'] = $variables;
        }

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->token,
            'Content-Type' => 'application/json',
        ])->post($this->endpoint, $payload);

        if (!$response->successful()) {
            throw new \Exception('Shopify HTTP Error: ' . $response->body());
        }

        $json = $response->json();

        if (!empty($json['errors'])) {
            throw new \Exception('Shopify GraphQL Error: ' . json_encode($json['errors']));
        }

        return $json;
    }

    public function syncCatalogItem(CatalogItem $item): CatalogItem
    {
        try {
            if (!$item->shopify_product_id) {
                $this->createProduct($item);
                $item->refresh();
            } else {
                $this->updateProduct($item);
                $item->refresh();
            }

            $this->syncInventory($item);

            $item->forceFill([
                'shopify_synced_at' => now(),
                'shopify_last_error' => null,
            ])->save();

            return $item;
        } catch (\Throwable $e) {
            Log::error('Shopify sync error', [
                'catalog_item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            $item->forceFill([
                'shopify_last_error' => $e->getMessage(),
            ])->save();

            throw $e;
        }
    }

    protected function createProduct(CatalogItem $item): void
    {
        $mutation = <<<'GRAPHQL'
mutation productCreate($product: ProductCreateInput!) {
  productCreate(product: $product) {
    product {
      id
      title
    }
    userErrors {
      field
      message
    }
  }
}
GRAPHQL;

        $variables = [
            'product' => [
                'title' => $this->cleanText($item->name, 'Producto JURETO'),
                'descriptionHtml' => $this->cleanHtml($item->description ?: $item->excerpt ?: ''),
                'vendor' => $this->cleanText($item->brand_name ?: 'JURETO', 'JURETO'),
                'productType' => $this->cleanText(
                    optional($item->categoryProduct)->name ?: $item->category_label ?: 'General',
                    'General'
                ),
                'status' => $item->status ? 'ACTIVE' : 'DRAFT',
            ],
        ];

        $result = $this->graphql($mutation, $variables);

        $errors = $result['data']['productCreate']['userErrors'] ?? [];

        if (!empty($errors)) {
            throw new \Exception('Shopify productCreate: ' . json_encode($errors));
        }

        $product = $result['data']['productCreate']['product'] ?? null;

        if (!$product || empty($product['id'])) {
            throw new \Exception('Shopify productCreate: no se recibió product.id');
        }

        $item->forceFill([
            'shopify_product_id' => $product['id'],
            'shopify_location_id' => config('services.shopify.location_id'),
        ])->save();

        $item->refresh();

        $this->createVariant($item);

        $item->refresh();

        $this->refreshVariantData($item);
    }

    protected function createVariant(CatalogItem $item): void
    {
        if (!$item->shopify_product_id) {
            throw new \Exception('No se puede crear variante: falta shopify_product_id.');
        }

        $mutation = <<<'GRAPHQL'
mutation productVariantsBulkCreate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
  productVariantsBulkCreate(
    productId: $productId,
    strategy: REMOVE_STANDALONE_VARIANT,
    variants: $variants
  ) {
    productVariants {
      id
      sku
      price
      barcode
      inventoryPolicy
      inventoryItem {
        id
        tracked
      }
    }
    userErrors {
      field
      message
    }
  }
}
GRAPHQL;

        $price = $item->sale_price ?: $item->price;

        if (!$price || (float) $price <= 0) {
            $price = 1;
        }

        $sku = $item->sku ?: 'JURETO-' . $item->id;

        $variables = [
            'productId' => $item->shopify_product_id,
            'variants' => [
                [
                    'price' => number_format((float) $price, 2, '.', ''),
                    'barcode' => $this->validBarcode($item->meli_gtin ?: $item->sku),
                    'inventoryPolicy' => 'DENY',
                    'inventoryItem' => [
                        'sku' => $sku,
                        'tracked' => true,
                    ],
                    'optionValues' => [
                        [
                            'name' => 'Default Title',
                            'optionName' => 'Title',
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->graphql($mutation, $variables);

        $errors = $result['data']['productVariantsBulkCreate']['userErrors'] ?? [];

        if (!empty($errors)) {
            throw new \Exception('Shopify productVariantsBulkCreate: ' . json_encode($errors));
        }

        $variant = $result['data']['productVariantsBulkCreate']['productVariants'][0] ?? null;

        if (!$variant || empty($variant['id'])) {
            throw new \Exception('Shopify productVariantsBulkCreate: no se recibió variant.id');
        }

        $item->forceFill([
            'shopify_variant_id' => $variant['id'],
            'shopify_inventory_item_id' => $variant['inventoryItem']['id'] ?? null,
        ])->save();
    }

    protected function updateProduct(CatalogItem $item): void
    {
        if (!$item->shopify_product_id) {
            throw new \Exception('No se puede actualizar producto: falta shopify_product_id.');
        }

        $mutation = <<<'GRAPHQL'
mutation productUpdate($product: ProductUpdateInput!) {
  productUpdate(product: $product) {
    product {
      id
      title
    }
    userErrors {
      field
      message
    }
  }
}
GRAPHQL;

        $variables = [
            'product' => [
                'id' => $item->shopify_product_id,
                'title' => $this->cleanText($item->name, 'Producto JURETO'),
                'descriptionHtml' => $this->cleanHtml($item->description ?: $item->excerpt ?: ''),
                'vendor' => $this->cleanText($item->brand_name ?: 'JURETO', 'JURETO'),
                'productType' => $this->cleanText(
                    optional($item->categoryProduct)->name ?: $item->category_label ?: 'General',
                    'General'
                ),
                'status' => $item->status ? 'ACTIVE' : 'DRAFT',
            ],
        ];

        $result = $this->graphql($mutation, $variables);

        $errors = $result['data']['productUpdate']['userErrors'] ?? [];

        if (!empty($errors)) {
            throw new \Exception('Shopify productUpdate: ' . json_encode($errors));
        }

        if (!$item->shopify_variant_id || !$item->shopify_inventory_item_id) {
            $this->refreshVariantData($item);
            $item->refresh();
        }

        if (!$item->shopify_variant_id) {
            $this->createVariant($item);
            $item->refresh();
        } else {
            $this->updateVariant($item);
        }
    }

    protected function updateVariant(CatalogItem $item): void
    {
        if (!$item->shopify_product_id) {
            throw new \Exception('No se puede actualizar variante: falta shopify_product_id.');
        }

        if (!$item->shopify_variant_id) {
            $this->refreshVariantData($item);
            $item->refresh();
        }

        if (!$item->shopify_variant_id) {
            throw new \Exception('No se puede actualizar variante: falta shopify_variant_id.');
        }

        $mutation = <<<'GRAPHQL'
mutation productVariantsBulkUpdate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
  productVariantsBulkUpdate(productId: $productId, variants: $variants) {
    productVariants {
      id
      sku
      price
      barcode
      inventoryPolicy
      inventoryItem {
        id
        tracked
      }
    }
    userErrors {
      field
      message
    }
  }
}
GRAPHQL;

        $price = $item->sale_price ?: $item->price;

        if (!$price || (float) $price <= 0) {
            $price = 1;
        }

        $sku = $item->sku ?: 'JURETO-' . $item->id;

        $variables = [
            'productId' => $item->shopify_product_id,
            'variants' => [
                [
                    'id' => $item->shopify_variant_id,
                    'price' => number_format((float) $price, 2, '.', ''),
                    'barcode' => $this->validBarcode($item->meli_gtin ?: $item->sku),
                    'inventoryPolicy' => 'DENY',
                    'inventoryItem' => [
                        'sku' => $sku,
                        'tracked' => true,
                    ],
                ],
            ],
        ];

        $result = $this->graphql($mutation, $variables);

        $errors = $result['data']['productVariantsBulkUpdate']['userErrors'] ?? [];

        if (!empty($errors)) {
            throw new \Exception('Shopify productVariantsBulkUpdate: ' . json_encode($errors));
        }

        $variant = $result['data']['productVariantsBulkUpdate']['productVariants'][0] ?? null;

        if ($variant) {
            $item->forceFill([
                'shopify_variant_id' => $variant['id'] ?? $item->shopify_variant_id,
                'shopify_inventory_item_id' => $variant['inventoryItem']['id'] ?? $item->shopify_inventory_item_id,
            ])->save();
        }
    }

    protected function refreshVariantData(CatalogItem $item): void
    {
        if (!$item->shopify_product_id) {
            return;
        }

        $query = <<<'GRAPHQL'
query getProductVariant($id: ID!) {
  product(id: $id) {
    id
    variants(first: 10) {
      edges {
        node {
          id
          sku
          inventoryItem {
            id
            tracked
          }
        }
      }
    }
  }
}
GRAPHQL;

        $result = $this->graphql($query, [
            'id' => $item->shopify_product_id,
        ]);

        $edges = $result['data']['product']['variants']['edges'] ?? [];

        if (empty($edges)) {
            return;
        }

        $sku = $item->sku ?: 'JURETO-' . $item->id;

        $selectedVariant = null;

        foreach ($edges as $edge) {
            $node = $edge['node'] ?? null;

            if (!$node) {
                continue;
            }

            if (($node['sku'] ?? null) === $sku) {
                $selectedVariant = $node;
                break;
            }
        }

        if (!$selectedVariant) {
            $selectedVariant = $edges[0]['node'] ?? null;
        }

        if (!$selectedVariant) {
            return;
        }

        $item->forceFill([
            'shopify_variant_id' => $selectedVariant['id'] ?? $item->shopify_variant_id,
            'shopify_inventory_item_id' => $selectedVariant['inventoryItem']['id'] ?? $item->shopify_inventory_item_id,
        ])->save();
    }

    public function syncInventory(CatalogItem $item): void
    {
        if (!$item->shopify_inventory_item_id) {
            $this->refreshVariantData($item);
            $item->refresh();
        }

        if (!$item->shopify_inventory_item_id) {
            throw new \Exception('El producto no tiene shopify_inventory_item_id.');
        }

        $locationId = config('services.shopify.location_id');

        if (!$locationId) {
            throw new \Exception('Falta configurar SHOPIFY_LOCATION_ID.');
        }

        $currentQuantity = $this->getCurrentInventoryQuantity(
            $item->shopify_inventory_item_id,
            $locationId
        );

        $mutation = <<<'GRAPHQL'
mutation inventorySetQuantities($input: InventorySetQuantitiesInput!, $idempotencyKey: String!) {
  inventorySetQuantities(input: $input) @idempotent(key: $idempotencyKey) {
    inventoryAdjustmentGroup {
      createdAt
      reason
      referenceDocumentUri
      changes {
        name
        delta
        quantityAfterChange
      }
    }
    userErrors {
      field
      message
      code
    }
  }
}
GRAPHQL;

        $variables = [
            'idempotencyKey' => (string) Str::uuid(),

            'input' => [
                'name' => 'available',
                'reason' => 'correction',
                'referenceDocumentUri' => 'gid://jureto/CatalogItem/' . $item->id,
                'quantities' => [
                    [
                        'inventoryItemId' => $item->shopify_inventory_item_id,
                        'locationId' => $locationId,
                        'quantity' => max(0, (int) $item->stock),
                        'changeFromQuantity' => $currentQuantity,
                    ],
                ],
            ],
        ];

        $result = $this->graphql($mutation, $variables);

        $errors = $result['data']['inventorySetQuantities']['userErrors'] ?? [];

        if (!empty($errors)) {
            throw new \Exception('Shopify inventorySetQuantities: ' . json_encode($errors));
        }
    }

    protected function getCurrentInventoryQuantity(string $inventoryItemId, string $locationId): int
    {
        $query = <<<'GRAPHQL'
query inventoryItemCurrentQuantity($id: ID!) {
  inventoryItem(id: $id) {
    id
    inventoryLevels(first: 50) {
      edges {
        node {
          id
          location {
            id
            name
          }
          quantities(names: ["available"]) {
            name
            quantity
          }
        }
      }
    }
  }
}
GRAPHQL;

        $result = $this->graphql($query, [
            'id' => $inventoryItemId,
        ]);

        $levels = $result['data']['inventoryItem']['inventoryLevels']['edges'] ?? [];

        foreach ($levels as $edge) {
            $node = $edge['node'] ?? null;

            if (!$node) {
                continue;
            }

            $nodeLocationId = $node['location']['id'] ?? null;

            if ($nodeLocationId !== $locationId) {
                continue;
            }

            foreach (($node['quantities'] ?? []) as $quantityRow) {
                if (($quantityRow['name'] ?? null) === 'available') {
                    return (int) ($quantityRow['quantity'] ?? 0);
                }
            }
        }

        return 0;
    }

    protected function cleanText(?string $value, string $fallback = ''): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $fallback;
        }

        return mb_substr($value, 0, 255);
    }

    protected function cleanHtml(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return nl2br(e($value));
    }

    protected function validBarcode(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (!preg_match('/^[0-9]{8,14}$/', $value)) {
            return null;
        }

        return $value;
    }
}