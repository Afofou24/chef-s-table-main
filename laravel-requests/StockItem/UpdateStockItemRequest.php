<?php

namespace App\Http\Requests\StockItem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'manager']);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('stock_items')->ignore($this->stock_item)],
            'category' => ['nullable', 'string', 'max:100'],
            'unit' => ['sometimes', 'string', 'max:20'],
            'minimum_quantity' => ['sometimes', 'numeric', 'min:0', 'max:999999.99'],
            'cost_per_unit' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'supplier' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Le nom ne peut pas dépasser 150 caractères.',
            'sku.unique' => 'Ce code SKU existe déjà.',
            'minimum_quantity.numeric' => 'La quantité minimale doit être un nombre.',
            'cost_per_unit.numeric' => 'Le coût unitaire doit être un nombre.',
        ];
    }
}
