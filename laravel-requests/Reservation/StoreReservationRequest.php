<?php

namespace App\Http\Requests\Reservation;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'manager', 'host']);
    }

    public function rules(): array
    {
        return [
            'table_id' => ['required', 'exists:restaurant_tables,id'],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/^[\d\s\+\-\(\)]+$/'],
            'customer_email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'party_size' => ['required', 'integer', 'min:1', 'max:50'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:30', 'max:480'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'table_id.required' => 'La table est obligatoire.',
            'table_id.exists' => 'La table sélectionnée n\'existe pas.',
            'customer_name.required' => 'Le nom du client est obligatoire.',
            'customer_name.max' => 'Le nom ne peut pas dépasser 150 caractères.',
            'customer_phone.required' => 'Le téléphone est obligatoire.',
            'customer_phone.regex' => 'Le numéro de téléphone n\'est pas valide.',
            'customer_email.email' => 'L\'adresse email n\'est pas valide.',
            'party_size.required' => 'Le nombre de personnes est obligatoire.',
            'party_size.min' => 'Le nombre de personnes doit être au moins 1.',
            'party_size.max' => 'Le nombre de personnes ne peut pas dépasser 50.',
            'reservation_date.required' => 'La date est obligatoire.',
            'reservation_date.after_or_equal' => 'La date doit être aujourd\'hui ou ultérieure.',
            'reservation_time.required' => 'L\'heure est obligatoire.',
            'reservation_time.date_format' => 'Le format de l\'heure doit être HH:MM.',
            'duration_minutes.min' => 'La durée minimale est de 30 minutes.',
            'duration_minutes.max' => 'La durée maximale est de 8 heures.',
        ];
    }
}
