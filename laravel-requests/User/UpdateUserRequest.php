<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin') || $this->user()->id === $this->user->id;
    }

    public function rules(): array
    {
        return [
            'username' => ['sometimes', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/', Rule::unique('users')->ignore($this->user)],
            'email' => ['sometimes', 'email:rfc,dns', 'max:255', Rule::unique('users')->ignore($this->user)],
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[\d\s\+\-\(\)]+$/'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_active' => ['boolean'],
            'roles' => ['sometimes', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'Ce nom d\'utilisateur est déjà pris.',
            'username.regex' => 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres et underscores.',
            'email.email' => 'L\'adresse email n\'est pas valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'phone.regex' => 'Le numéro de téléphone n\'est pas valide.',
            'avatar.image' => 'Le fichier doit être une image.',
            'avatar.max' => 'L\'image ne doit pas dépasser 2 Mo.',
            'roles.*.exists' => 'Le rôle sélectionné n\'existe pas.',
        ];
    }
}
