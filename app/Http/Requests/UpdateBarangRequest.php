<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_jenis_barang' => 'sometimes|required|string|exists:tb_jenis_barang,id_jenis_barang',
            'nama_barang'     => 'sometimes|required|string|max:255',
            'harga_barang'    => 'sometimes|required|numeric|min:0',
            'deskripsi'       => 'nullable|string|max:1000',
            'satuan'          => 'nullable|string|max:50',
            'batas_minimum'   => 'sometimes|integer|min:0',
        ];
    }
}