<?php

namespace App\Http\Requests\Payment;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'manager', 'cashier']);
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'exists:orders,id', 'unique:payments,order_id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'payment_method' => ['required', 'string', Rule::in([
                Payment::METHOD_CASH,
                Payment::METHOD_CARD,
                Payment::METHOD_MOBILE,
                Payment::METHOD_OTHER,
            ])],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'La commande est obligatoire.',
            'order_id.exists' => 'La commande sélectionnée n\'existe pas.',
            'order_id.unique' => 'Cette commande a déjà été payée.',
            'amount.required' => 'Le montant est obligatoire.',
            'amount.numeric' => 'Le montant doit être un nombre.',
            'amount.min' => 'Le montant doit être supérieur à 0.',
            'payment_method.required' => 'Le mode de paiement est obligatoire.',
            'payment_method.in' => 'Le mode de paiement sélectionné n\'est pas valide.',
        ];
    }
}
