<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'category' => $this->category,
            'unit' => $this->unit,
            'quantity' => (float) $this->quantity,
            'minimum_quantity' => (float) $this->minimum_quantity,
            'cost_per_unit' => (float) $this->cost_per_unit,
            'total_value' => (float) $this->total_value,
            'supplier' => $this->supplier,
            'is_low_stock' => $this->isLowStock(),
            'last_restocked_at' => $this->last_restocked_at?->toISOString(),
            'movements_count' => $this->whenCounted('movements'),
            'recent_movements' => StockMovementResource::collection($this->whenLoaded('movements')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
