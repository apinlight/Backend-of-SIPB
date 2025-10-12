<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePenggunaanBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        // We will use the policy for authorization in the controller method itself.
        return true;
    }

    public function rules(): array
    {
        return [
            'id_barang' => 'required|string|exists:tb_barang,id_barang',
            'jumlah_digunakan' => 'required|integer|min:1',
            'keperluan' => 'required|string|max:255',
            'tanggal_penggunaan' => 'required|date',
            'keterangan' => 'nullable|string|max:1000',
        ];
    }
}
