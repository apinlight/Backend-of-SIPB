<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $currentUser = $this->user();

        // Check if the current user is authorized to create a user at all
        if (!$currentUser->can('create', \App\Models\User::class)) {
            return false;
        }

        // Additional security: A manager cannot create a user outside their branch
        if ($currentUser->hasRole('manager') && $this->branch_name !== $currentUser->branch_name) {
            return false;
        }

        // A manager cannot assign the 'admin' role
        if ($currentUser->hasRole('manager') && in_array('admin', $this->roles ?? [])) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'unique_id'   => 'required|string|unique:tb_users,unique_id',
            'username'    => 'required|string|max:255|unique:tb_users,username',
            'email'       => 'required|email|max:255|unique:tb_users,email',
            'password'    => ['required', 'confirmed', Password::min(6)],
            'branch_name' => 'required|string|max:255',
            'is_active'   => 'sometimes|boolean',
            'roles'       => 'sometimes|array',
            'roles.*'     => 'string|exists:roles,name',
        ];
    }
}