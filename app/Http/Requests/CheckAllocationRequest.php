<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unique_id' => 'required|string|exists:tb_users,unique_id',
            'items' => 'required|array|min:1',
            'items.*.id_barang' => 'required|string|exists:tb_barang,id_barang',
            'items.*.jumlah' => 'required|integer|min:1',
        ];
    }
}
