<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'tb_barang';
    protected $primaryKey = 'id_barang';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id_barang', 'nama_barang'];

    // Many-to-many: a barang is stored in many users' gudang records
    public function gudang()
    {
        return $this->belongsToMany(User::class, 'tb_gudang', 'id_barang', 'unique_id')
                    ->using(Gudang::class)
                    ->withPivot('jumlah_barang');
    }
}
