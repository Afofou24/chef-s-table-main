<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'manager', 'server']);
    }

    public function rules(): array
    {
        return [
            'table_id' => ['required', 'exists:restaurant_tables,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'table_id.required' => 'La table est obligatoire.',
            'table_id.exists' => 'La table sélectionnée n\'existe pas.',
            'items.required' => 'Au moins un article est obligatoire.',
            'items.min' => 'Au moins un article est obligatoire.',
            'items.*.menu_item_id.required' => 'L\'article du menu est obligatoire.',
            'items.*.menu_item_id.exists' => 'L\'article du menu sélectionné n\'existe pas.',
            'items.*.quantity.required' => 'La quantité est obligatoire.',
            'items.*.quantity.min' => 'La quantité doit être au moins 1.',
            'items.*.quantity.max' => 'La quantité ne peut pas dépasser 99.',
        ];
    }
}
