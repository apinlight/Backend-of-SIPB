<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $currentUser = $this->user();
        $targetUser = $this->route('user'); // Gets the user from the route

        // Check if the current user is authorized to update the target user
        if (! $currentUser->can('update', $targetUser)) {
            return false;
        }

        // A manager cannot move a user to another branch
        if ($currentUser->hasRole('manager') && $this->branch_name !== $targetUser->branch_name) {
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
        $targetUser = $this->route('user');

        return [
            'username' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('tb_users', 'username')->ignore($targetUser->unique_id, 'unique_id'),
            ],
            'email' => [
                'sometimes', 'required', 'email', 'max:255',
                Rule::unique('tb_users', 'email')->ignore($targetUser->unique_id, 'unique_id'),
            ],
            'password' => ['sometimes', 'nullable', 'confirmed', Password::min(6)],
            'branch_name' => 'sometimes|required|string|max:255',
            'is_active' => 'sometimes|boolean',
            'roles' => 'sometimes|array',
            'roles.*' => 'string|exists:roles,name',
        ];
    }
}
