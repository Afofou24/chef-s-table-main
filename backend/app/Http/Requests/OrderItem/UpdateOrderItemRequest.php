<?php

namespace App\Http\Requests\OrderItem;

use App\Models\OrderItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'manager', 'server', 'kitchen']);
    }

    public function rules(): array
    {
        return [
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:99'],
            'notes' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in([
                OrderItem::STATUS_PENDING,
                OrderItem::STATUS_PREPARING,
                OrderItem::STATUS_READY,
                OrderItem::STATUS_SERVED,
                OrderItem::STATUS_CANCELLED,
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.min' => 'La quantité doit être au moins 1.',
            'quantity.max' => 'La quantité ne peut pas dépasser 99.',
            'status.in' => 'Le statut sélectionné n\'est pas valide.',
        ];
    }
}
