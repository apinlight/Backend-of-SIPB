<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetMonthlyLimitRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The controller's middleware already checks for the 'admin' role.
        return true;
    }

    public function rules(): array
    {
        return [
            'monthly_limit' => 'required|integer|min:1|max:50',
        ];
    }
}
