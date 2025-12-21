<?php

namespace App\Http\Requests\Setting;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'value' => ['required'],
            'type' => ['sometimes', 'string', Rule::in([
                Setting::TYPE_STRING,
                Setting::TYPE_INTEGER,
                Setting::TYPE_BOOLEAN,
                Setting::TYPE_JSON,
            ])],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'value.required' => 'La valeur est obligatoire.',
            'type.in' => 'Le type sélectionné n\'est pas valide.',
        ];
    }
}
