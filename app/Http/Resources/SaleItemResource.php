<?php

namespace App\Http\Resources;

use App\Models\SaleItem;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SaleItem
 */
class SaleItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'product_id' => (int) $this->product_id,
            'product_name' => (string) $this->product_name,
            'unit_price_cents' => (int) $this->unit_price_cents,
            'quantity' => (int) $this->quantity,
            'line_total_cents' => (int) $this->line_total_cents,
        ];
    }
}
