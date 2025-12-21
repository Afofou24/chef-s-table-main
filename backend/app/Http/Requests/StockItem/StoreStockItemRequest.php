<?php

namespace App\Http\Requests\StockItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'manager']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'sku' => ['nullable', 'string', 'max:50', 'unique:stock_items,sku'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:20'],
            'quantity' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'minimum_quantity' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'cost_per_unit' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'supplier' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de l\'article est obligatoire.',
            'name.max' => 'Le nom ne peut pas dépasser 150 caractères.',
            'sku.unique' => 'Ce code SKU existe déjà.',
            'unit.required' => 'L\'unité de mesure est obligatoire.',
            'quantity.required' => 'La quantité est obligatoire.',
            'quantity.numeric' => 'La quantité doit être un nombre.',
            'quantity.min' => 'La quantité ne peut pas être négative.',
            'minimum_quantity.required' => 'La quantité minimale est obligatoire.',
            'minimum_quantity.numeric' => 'La quantité minimale doit être un nombre.',
            'cost_per_unit.numeric' => 'Le coût unitaire doit être un nombre.',
        ];
    }
}
