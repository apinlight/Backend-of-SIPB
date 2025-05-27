<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPengajuan extends Model
{
    protected $table = 'tb_detail_pengajuan';
    protected $primaryKey = null;
    public $incrementing = false;
    
    // Composite key: id_pengajuan and id_barang
    protected $fillable = ['id_pengajuan', 'id_barang'];

    // Belongs to a Pengajuan
    public function pengajuan()
    {
        return $this->belongsTo(Pengajuan::class, 'id_pengajuan', 'id_pengajuan');
    }

    // Belongs to a Barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    public function setKeysForSaveQuery($query)
    {
        $query->where('id_pengajuan', '=', $this->getAttribute('id_pengajuan'))
              ->where('id_barang', '=', $this->getAttribute('id_barang'));
        return $query;
    }
}
