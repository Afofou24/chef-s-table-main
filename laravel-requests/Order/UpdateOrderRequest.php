<?php

namespace App\Http\Requests\Order;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'manager', 'server', 'kitchen']);
    }

    public function rules(): array
    {
        return [
            'table_id' => ['sometimes', 'exists:restaurant_tables,id'],
            'status' => ['sometimes', 'string', Rule::in([
                Order::STATUS_PENDING,
                Order::STATUS_IN_PROGRESS,
                Order::STATUS_READY,
                Order::STATUS_DELIVERED,
                Order::STATUS_PAID,
                Order::STATUS_CANCELLED,
            ])],
            'notes' => ['nullable', 'string', 'max:500'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'table_id.exists' => 'La table sélectionnée n\'existe pas.',
            'status.in' => 'Le statut sélectionné n\'est pas valide.',
            'discount_amount.numeric' => 'La remise doit être un nombre.',
            'discount_amount.min' => 'La remise ne peut pas être négative.',
        ];
    }
}
