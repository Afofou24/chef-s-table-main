<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100', Rule::unique('roles')->ignore($this->role)],
            'code' => ['sometimes', 'string', 'max:50', 'regex:/^[a-z_]+$/', Rule::unique('roles')->ignore($this->role)],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ce nom de rôle existe déjà.',
            'code.unique' => 'Ce code de rôle existe déjà.',
            'code.regex' => 'Le code doit contenir uniquement des lettres minuscules et des underscores.',
        ];
    }
}
