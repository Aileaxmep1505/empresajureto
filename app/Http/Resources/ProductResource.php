<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'sku'         => $this->sku,
            'price'       => (float) $this->price,
            'currency'    => $this->currency ?? 'MXN',
            'stock'       => (int) ($this->stock ?? 0),
            'image_url'   => $this->image_url ?? null,
            'description' => $this->description ?? null,
            'short'       => $this->short_description ?? null,
        ];
    }
}
