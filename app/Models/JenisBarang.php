<?php
// app/Models/JenisBarang.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $id_jenis_barang
 * @property string $nama_jenis_barang
 * @property bool $is_active
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Barang> $barang
 * @property-read int|null $barang_count
 * @method static \Database\Factories\JenisBarangFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisBarang newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisBarang newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisBarang query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisBarang whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisBarang whereIdJenisBarang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisBarang whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisBarang whereNamaJenisBarang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JenisBarang whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class JenisBarang extends Model
{
    use HasUlids, HasFactory;

    protected $table = 'tb_jenis_barang';
    protected $primaryKey = 'id_jenis_barang';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    
    // ✅ ADD is_active to fillable
    protected $fillable = ['id_jenis_barang', 'nama_jenis_barang', 'is_active'];
    
    // ✅ ADD casts for boolean
    protected $casts = [
        'is_active' => 'boolean',
    ];

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
            // ✅ Set default is_active
            if (!isset($model->is_active)) {
                $model->is_active = true;
            }
        });
    }
}
