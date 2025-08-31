<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'tb_barang';
    protected $primaryKey = 'id_barang';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id_barang',
        'nama_barang',
        'id_jenis_barang',
        'harga_barang',
        'deskripsi',
        'satuan',
        'batas_minimum'
    ];

    public function jenisBarang(): BelongsTo
    {
        return $this->belongsTo(JenisBarang::class, 'id_jenis_barang', 'id_jenis_barang');
    }

    public function batasBarang(): HasOne
    {
        return $this->hasOne(BatasBarang::class, 'id_barang', 'id_barang');
    }
    
    // A Barang has many individual stock records in the Gudang
    public function gudangEntries(): HasMany
    {
        return $this->hasMany(Gudang::class, 'id_barang', 'id_barang');
    }

    public function detailPengajuan(): HasMany
    {
        return $this->hasMany(DetailPengajuan::class, 'id_barang', 'id_barang');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id_barang)) {
                $model->id_barang = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }
}