<?php

namespace App\Http\Requests;

use App\Models\Pengajuan;
use Illuminate\Foundation\Http\FormRequest;

class StoreDetailPengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pengajuan = Pengajuan::find($this->input('id_pengajuan'));

        if (!$pengajuan) {
            return false;
        }

        // A user can add a detail if they are authorized to update the parent pengajuan.
        return $this->user()->can('update', $pengajuan);
    }

    public function rules(): array
    {
        return [
            'id_pengajuan' => 'required|string|exists:tb_pengajuan,id_pengajuan',
            'id_barang'    => 'required|string|exists:tb_barang,id_barang',
            'jumlah'       => 'required|integer|min:1',
            'keterangan'   => 'nullable|string|max:500',
        ];
    }
}