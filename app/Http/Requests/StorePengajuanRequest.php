<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $currentUser = $this->user();

        // Check if the user is allowed to create a Pengajuan at all.
        if (! $currentUser->can('create', \App\Models\Pengajuan::class)) {
            return false;
        }

        // A regular user cannot create a request for someone else.
        if ($currentUser->hasRole('user') && $this->unique_id !== $currentUser->unique_id) {
            return false;
        }

        // Only an admin can create a request for someone else.
        if (! $currentUser->hasRole('admin') && $this->unique_id !== $currentUser->unique_id) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'id_pengajuan' => 'required|string|unique:tb_pengajuan,id_pengajuan',
            'unique_id' => 'required|string|exists:tb_users,unique_id',
            'status_pengajuan' => 'sometimes|in:Menunggu Persetujuan,Disetujui,Ditolak',
            'tipe_pengajuan' => 'sometimes|in:manual,biasa,mandiri',
            'keterangan' => 'nullable|string|max:500',
            'bukti_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120', // Added PDF
            'items' => 'sometimes|array|min:1',
            'items.*.id_barang' => 'required_with:items|string|exists:tb_barang,id_barang',
            'items.*.jumlah' => 'required_with:items|integer|min:1',
        ];
    }
}
