<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'code' => ['required', 'string', 'max:50', 'unique:roles,code', 'regex:/^[a-z_]+$/'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du rôle est obligatoire.',
            'name.unique' => 'Ce nom de rôle existe déjà.',
            'code.required' => 'Le code du rôle est obligatoire.',
            'code.unique' => 'Ce code de rôle existe déjà.',
            'code.regex' => 'Le code doit contenir uniquement des lettres minuscules et des underscores.',
        ];
    }
}
