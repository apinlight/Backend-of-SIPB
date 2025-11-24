<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        // Delegasikan ke policy (PengajuanPolicy::create) tanpa memaksa client mengirim unique_id.
        return $user && $user->can('create', \App\Models\Pengajuan::class);
    }

    public function rules(): array
    {
        return [
            'id_pengajuan' => 'sometimes|string|unique:tb_pengajuan,id_pengajuan',
            // unique_id tidak lagi dikirim oleh client: di-inject dari user terautentik.
            'status_pengajuan' => 'sometimes|in:Menunggu Persetujuan,Disetujui,Ditolak',
            'tipe_pengajuan' => 'sometimes|in:manual,biasa,mandiri',
            'keterangan' => 'nullable|string|max:500',
            'bukti_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'items' => 'sometimes|array|min:1',
            'items.*.id_barang' => 'required_with:items|string|exists:tb_barang,id_barang',
            'items.*.jumlah' => 'required_with:items|integer|min:1',
        ];
    }
}
