<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Check if registration is enabled in the config.
        return config('auth.allow_registration', false);
    }

    public function rules(): array
    {
        return [
            'unique_id'   => 'required|string|unique:tb_users,unique_id',
            'username'    => 'required|string|max:255|unique:tb_users,username|regex:/^[a-zA-Z0-9_]+$/',
            'email'       => 'required|string|email|max:255|unique:tb_users,email',
            'password'    => ['required', 'confirmed', Password::min(8)],
            'branch_name' => 'required|string|max:255',
        ];
    }
}