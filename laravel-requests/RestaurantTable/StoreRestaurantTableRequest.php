<?php

namespace App\Http\Requests\RestaurantTable;

use App\Models\RestaurantTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRestaurantTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'manager']);
    }

    public function rules(): array
    {
        return [
            'number' => ['required', 'integer', 'min:1', 'max:999', 'unique:restaurant_tables,number'],
            'name' => ['nullable', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'location' => ['required', 'string', Rule::in([
                RestaurantTable::LOCATION_INDOOR,
                RestaurantTable::LOCATION_OUTDOOR,
                RestaurantTable::LOCATION_BAR,
                RestaurantTable::LOCATION_VIP,
            ])],
            'status' => ['sometimes', 'string', Rule::in([
                RestaurantTable::STATUS_AVAILABLE,
                RestaurantTable::STATUS_OCCUPIED,
                RestaurantTable::STATUS_RESERVED,
                RestaurantTable::STATUS_MAINTENANCE,
            ])],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'number.required' => 'Le numéro de table est obligatoire.',
            'number.unique' => 'Ce numéro de table existe déjà.',
            'number.min' => 'Le numéro de table doit être au moins 1.',
            'capacity.required' => 'La capacité est obligatoire.',
            'capacity.min' => 'La capacité doit être au moins 1 personne.',
            'capacity.max' => 'La capacité ne peut pas dépasser 50 personnes.',
            'location.required' => 'L\'emplacement est obligatoire.',
            'location.in' => 'L\'emplacement sélectionné n\'est pas valide.',
            'status.in' => 'Le statut sélectionné n\'est pas valide.',
        ];
    }
}
