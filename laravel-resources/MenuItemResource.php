<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'formatted_price' => $this->formatted_price,
            'image' => $this->image,
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,
            'preparation_time' => $this->preparation_time,
            'is_available' => $this->is_available,
            'is_featured' => $this->is_featured,
            'allergens' => $this->allergens,
            'calories' => $this->calories,
            'order_items_count' => $this->whenCounted('orderItems'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
