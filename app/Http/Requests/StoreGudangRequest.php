<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreGudangRequest extends FormRequest
{
    public function authorize(): bool
    {
        $currentUser = $this->user();
        $targetCabangId = $this->input('id_cabang');

        if ($currentUser->hasRole('admin')) {
            return true;
        }

        if ($currentUser->hasRole('manager')) {
            // Manager can add to their own branch stock only.
            return $targetCabangId === $currentUser->id_cabang;
        }

        // Regular user can only add to their own branch stock.
        return $targetCabangId === $currentUser->id_cabang;
    }

    public function rules(): array
    {
        return [
            'id_cabang' => 'required|string|exists:tb_cabang,id_cabang',
            'id_barang' => 'required|string|exists:tb_barang,id_barang',
            'jumlah_barang' => 'required|integer|min:1',
            'keterangan' => 'nullable|string|max:500',
            'tipe' => 'sometimes|in:manual,biasa,mandiri',
        ];
    }
}
