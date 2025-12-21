<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,
            'order_id' => $this->order_id,
            'order' => new OrderResource($this->whenLoaded('order')),
            'amount' => (float) $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'payment_method' => $this->payment_method,
            'method_label' => $this->method_label,
            'status' => $this->status,
            'processed_by' => $this->processed_by,
            'cashier' => new UserResource($this->whenLoaded('processedBy')),
            'transaction_reference' => $this->transaction_reference,
            'notes' => $this->notes,
            'paid_at' => $this->paid_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
