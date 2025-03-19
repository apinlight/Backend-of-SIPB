<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatasBarang extends Model
{
    // Specify the table name
    protected $table = 'tb_batas_barang';

    // The primary key is id_barang and it's not auto-incrementing (likely a string)
    protected $primaryKey = 'id_barang';
    public $incrementing = false;
    protected $keyType = 'string';

    // Allow mass assignment for these fields
    protected $fillable = ['id_barang', 'batas_barang'];

    // Optional: Define a relationship to Barang if you need to access item details
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }
}
