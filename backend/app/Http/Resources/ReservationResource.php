<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'table_id' => $this->table_id,
            'table' => new RestaurantTableResource($this->whenLoaded('table')),
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'party_size' => $this->party_size,
            'reservation_date' => $this->reservation_date?->toDateString(),
            'reservation_time' => $this->reservation_time?->format('H:i'),
            'formatted_datetime' => $this->formatted_date_time,
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'can_be_cancelled' => $this->canBeCancelled(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
