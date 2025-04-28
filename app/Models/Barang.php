<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'tb_barang';
    protected $primaryKey = 'id_barang';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id_barang', 'nama_barang', 'id_jenis_barang', 'harga_barang'];

    // One-to-many: a barang has one jenis barang
    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class, 'id_jenis_barang');
    }
    // One-to-many: a barang has one batas barang
    public function batasBarang()
    {
        return $this->hasOne(BatasBarang::class, 'id_barang');
    }

    // Many-to-many: a barang is stored in many users' gudang records
    public function gudang()
    {
        return $this->belongsToMany(User::class, 'tb_gudang', 'id_barang', 'unique_id')
                    ->using(Gudang::class)
                    ->withPivot('jumlah_barang');
    }
}
