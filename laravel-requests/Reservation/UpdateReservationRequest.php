<?php

namespace App\Http\Requests\Reservation;

use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'manager', 'host']);
    }

    public function rules(): array
    {
        return [
            'table_id' => ['sometimes', 'exists:restaurant_tables,id'],
            'customer_name' => ['sometimes', 'string', 'max:150'],
            'customer_phone' => ['sometimes', 'string', 'max:20', 'regex:/^[\d\s\+\-\(\)]+$/'],
            'customer_email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'party_size' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'reservation_date' => ['sometimes', 'date'],
            'reservation_time' => ['sometimes', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:30', 'max:480'],
            'status' => ['sometimes', 'string', Rule::in([
                Reservation::STATUS_PENDING,
                Reservation::STATUS_CONFIRMED,
                Reservation::STATUS_CANCELLED,
                Reservation::STATUS_COMPLETED,
                Reservation::STATUS_NO_SHOW,
            ])],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'table_id.exists' => 'La table sélectionnée n\'existe pas.',
            'customer_name.max' => 'Le nom ne peut pas dépasser 150 caractères.',
            'customer_phone.regex' => 'Le numéro de téléphone n\'est pas valide.',
            'customer_email.email' => 'L\'adresse email n\'est pas valide.',
            'party_size.min' => 'Le nombre de personnes doit être au moins 1.',
            'party_size.max' => 'Le nombre de personnes ne peut pas dépasser 50.',
            'reservation_time.date_format' => 'Le format de l\'heure doit être HH:MM.',
            'status.in' => 'Le statut sélectionné n\'est pas valide.',
            'duration_minutes.min' => 'La durée minimale est de 30 minutes.',
            'duration_minutes.max' => 'La durée maximale est de 8 heures.',
        ];
    }
}
