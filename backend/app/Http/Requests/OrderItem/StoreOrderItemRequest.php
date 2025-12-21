<?php

namespace App\Http\Requests\OrderItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'manager', 'server']);
    }

    public function rules(): array
    {
        return [
            'menu_item_id' => ['required', 'exists:menu_items,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'menu_item_id.required' => 'L\'article du menu est obligatoire.',
            'menu_item_id.exists' => 'L\'article du menu sélectionné n\'existe pas.',
            'quantity.required' => 'La quantité est obligatoire.',
            'quantity.min' => 'La quantité doit être au moins 1.',
            'quantity.max' => 'La quantité ne peut pas dépasser 99.',
        ];
    }
}
