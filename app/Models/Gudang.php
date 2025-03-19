<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Gudang extends Pivot
{
    protected $table = 'tb_gudang';

    protected $fillable = ['unique_id', 'id_barang', 'jumlah_barang'];
    
    // Optional: define relationships if you want to access related models directly
    public function user()
    {
        return $this->belongsTo(User::class, 'unique_id', 'unique_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }
}
