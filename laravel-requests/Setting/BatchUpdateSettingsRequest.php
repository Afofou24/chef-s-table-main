<?php

namespace App\Http\Requests\Setting;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchUpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'min:1'],
            'settings.*.key' => ['required', 'string', 'max:100'],
            'settings.*.value' => ['required'],
            'settings.*.type' => ['sometimes', 'string', Rule::in([
                Setting::TYPE_STRING,
                Setting::TYPE_INTEGER,
                Setting::TYPE_BOOLEAN,
                Setting::TYPE_JSON,
            ])],
            'settings.*.group' => ['nullable', 'string', 'max:50'],
            'settings.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.required' => 'Au moins un paramètre est obligatoire.',
            'settings.*.key.required' => 'La clé du paramètre est obligatoire.',
            'settings.*.value.required' => 'La valeur du paramètre est obligatoire.',
            'settings.*.type.in' => 'Le type sélectionné n\'est pas valide.',
        ];
    }
}
