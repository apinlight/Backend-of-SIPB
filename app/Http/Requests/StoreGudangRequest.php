<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreGudangRequest extends FormRequest
{
    public function authorize(): bool
    {
        $currentUser = $this->user();
        $targetUserId = $this->input('unique_id');

        if ($currentUser->hasRole('admin')) {
            return true;
        }

        if ($currentUser->hasRole('manager')) {
            // Manager can add to their own stock or anyone in their branch.
            $targetUser = User::where('unique_id', $targetUserId)->first();

            return $targetUser && $targetUser->branch_name === $currentUser->branch_name;
        }

        // Regular user can only add to their own stock.
        return $targetUserId === $currentUser->unique_id;
    }

    public function rules(): array
    {
        return [
            'unique_id' => 'required|string|exists:tb_users,unique_id',
            'id_barang' => 'required|string|exists:tb_barang,id_barang',
            'jumlah_barang' => 'required|integer|min:1',
            'keterangan' => 'nullable|string|max:500',
            'tipe' => 'sometimes|in:manual,biasa,mandiri',
        ];
    }
}
