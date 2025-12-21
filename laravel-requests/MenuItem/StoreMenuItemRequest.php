<?php

namespace App\Http\Requests\MenuItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'manager']);
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'preparation_time' => ['nullable', 'integer', 'min:1', 'max:180'],
            'is_available' => ['boolean'],
            'is_featured' => ['boolean'],
            'allergens' => ['nullable', 'string', 'max:500'],
            'calories' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'La catégorie est obligatoire.',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'name.required' => 'Le nom du plat est obligatoire.',
            'name.max' => 'Le nom ne peut pas dépasser 150 caractères.',
            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.min' => 'Le prix ne peut pas être négatif.',
            'price.max' => 'Le prix ne peut pas dépasser 9999.99 €.',
            'image.image' => 'Le fichier doit être une image.',
            'image.max' => 'L\'image ne doit pas dépasser 2 Mo.',
            'preparation_time.integer' => 'Le temps de préparation doit être un nombre entier.',
            'preparation_time.max' => 'Le temps de préparation ne peut pas dépasser 180 minutes.',
            'calories.integer' => 'Les calories doivent être un nombre entier.',
        ];
    }
}
