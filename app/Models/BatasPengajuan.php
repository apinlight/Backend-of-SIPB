<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatasPengajuan extends Model
{
    // Specify the table name
    protected $table = 'tb_batas_pengajuan';

    // The primary key is id_barang and it's not auto-incrementing
    protected $primaryKey = 'id_barang';
    public $incrementing = false;
    protected $keyType = 'string';

    // Allow mass assignment for these fields
    protected $fillable = ['id_barang', 'batas_pengajuan'];

    // Optional: Define a relationship to Barang for easy access to item details
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }
}
