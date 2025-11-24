<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated user can update their own profile
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'username' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('tb_users', 'username')->ignore($user->unique_id, 'unique_id'),
            ],
            'email' => [
                'sometimes', 'required', 'email', 'max:255',
                Rule::unique('tb_users', 'email')->ignore($user->unique_id, 'unique_id'),
            ],
            'password' => ['sometimes', 'nullable', 'confirmed', Password::min(6)],
            // Cabang cannot be changed via profile update; use admin UI to move users between branches.
            // ✅ SECURITY: Users cannot change their own roles or active status
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
