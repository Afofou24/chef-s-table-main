<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantTableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'location' => $this->location,
            'location_label' => $this->location_label,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'is_active' => $this->is_active,
            'current_order' => new OrderResource($this->whenLoaded('currentOrder')),
            'orders_count' => $this->whenCounted('orders'),
            'reservations_count' => $this->whenCounted('reservations'),
            'today_reservations' => ReservationResource::collection($this->whenLoaded('todayReservations')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
