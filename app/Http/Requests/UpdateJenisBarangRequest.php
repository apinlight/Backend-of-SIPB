<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJenisBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $jenisBarangId = $this->route('jenis_barang')->id_jenis_barang;

        return [
            'nama_jenis_barang' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('tb_jenis_barang', 'nama_jenis_barang')->ignore($jenisBarangId, 'id_jenis_barang')
            ],
            'deskripsi'         => 'nullable|string|max:1000',
            'is_active'         => 'sometimes|boolean',
        ];
    }
}