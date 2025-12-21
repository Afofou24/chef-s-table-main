<?php

namespace App\Http\Requests\Backup;

use App\Models\Backup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in([
                Backup::TYPE_FULL,
                Backup::TYPE_PARTIAL,
            ])],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Le type de sauvegarde est obligatoire.',
            'type.in' => 'Le type de sauvegarde sélectionné n\'est pas valide.',
        ];
    }
}
