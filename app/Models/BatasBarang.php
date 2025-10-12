<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id_barang
 * @property int $batas_barang
 * @property int|null $harga_barang
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Barang $barang
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatasBarang newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatasBarang newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatasBarang query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatasBarang whereBatasBarang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatasBarang whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatasBarang whereHargaBarang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatasBarang whereIdBarang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BatasBarang whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
