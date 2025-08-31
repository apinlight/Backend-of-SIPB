<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJenisBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The policy will handle the core authorization check.
        return true;
    }

    public function rules(): array
    {
        return [
            'id_jenis_barang'   => 'required|string|unique:tb_jenis_barang,id_jenis_barang',
            'nama_jenis_barang' => 'required|string|max:255|unique:tb_jenis_barang,nama_jenis_barang',
            'deskripsi'         => 'nullable|string|max:1000',
            'is_active'         => 'sometimes|boolean',
        ];
    }
}