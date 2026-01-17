<?php

namespace App\Http\Resources;

use App\Models\Sale;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Sale
 */
class SaleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'status' => $this->status->value,
            'total_cents' => (int) $this->total_cents,
            'created_at' => $this->created_at?->toISOString(),
            'items' => SaleItemResource::collection(
                $this->whenLoaded('items')
            ),
        ];
    }
}
