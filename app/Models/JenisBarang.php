<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JenisBarang extends Model
{
    use HasFactory;
    protected $table = 'tb_jenis_barang';
    protected $primaryKey = 'id_jenis_barang';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id_jenis_barang', 'nama_jenis_barang'];

    // One-to-many: a jenis barang has many barang
    public function barang()
    {
        return $this->hasMany(Barang::class, 'id_jenis_barang');
    }
}