<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only an admin can perform this sensitive action.
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'adjustment_type'   => 'required|in:add,subtract,set',
            'adjustment_amount' => 'required|integer|min:0',
            'reason'            => 'required|string|max:500',
        ];
    }
}