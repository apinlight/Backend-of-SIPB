<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Gudang extends Pivot
{
    protected $table = 'tb_gudang';
    public $incrementing = false;
    protected $primaryKey = null;
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

    public function setKeysForSaveQuery($query)
    {
        $query->where('unique_id', '=', $this->getAttribute('unique_id'))
              ->where('id_barang', '=', $this->getAttribute('id_barang'));
        return $query;
    }
}
