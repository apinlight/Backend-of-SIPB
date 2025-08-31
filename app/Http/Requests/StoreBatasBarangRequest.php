<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatasBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy will handle authorization in the controller
    }

    public function rules(): array
    {
        return [
            'id_barang'    => 'required|string|exists:tb_barang,id_barang|unique:tb_batas_barang,id_barang',
            'batas_barang' => 'required|integer|min:0',
        ];
    }
}