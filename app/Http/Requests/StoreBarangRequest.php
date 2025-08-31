<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The policy will handle authorization in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'id_barang'       => 'required|string|unique:tb_barang,id_barang',
            'id_jenis_barang' => 'required|string|exists:tb_jenis_barang,id_jenis_barang',
            'nama_barang'     => 'required|string|max:255',
            'harga_barang'    => 'required|numeric|min:0',
            'deskripsi'       => 'nullable|string|max:1000',
            'satuan'          => 'nullable|string|max:50',
            'batas_minimum'   => 'sometimes|integer|min:0',
        ];
    }
}