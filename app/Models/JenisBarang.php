<?php

// app/Models/JenisBarang.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class JenisBarang extends Model
{
    use HasUlids;

    protected $table = 'tb_jenis_barang';
    protected $primaryKey = 'id_jenis_barang';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['id_jenis_barang', 'nama_jenis_barang'];

    // Relasi: satu jenis punya banyak barang
    public function barang()
    {
        return $this->hasMany(Barang::class, 'id_jenis_barang', 'id_jenis_barang');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id_jenis_barang)) {
                $model->id_jenis_barang = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }
}
