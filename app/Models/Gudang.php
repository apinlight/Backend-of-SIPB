<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * 
 *
 * @property string $unique_id
 * @property string $id_barang
 * @property int $jumlah_barang
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Barang $barang
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang whereIdBarang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang whereJumlahBarang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
