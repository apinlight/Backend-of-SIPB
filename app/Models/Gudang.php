<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property string $unique_id
 * @property string $id_barang
 * @property int $jumlah_barang
 * @property string|null $keterangan
 * @property int|null $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Barang $barang
 * @property-read \App\Models\User $user
 * 
 * ✅ DYNAMIC PROPERTIES (added by controllers/services)
 * @property float|null $total_nilai
 * @property string|null $stock_status
 * 
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang whereIdBarang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang whereJumlahBarang($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang whereKeterangan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gudang whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Gudang extends Pivot
{
    protected $table = 'tb_gudang';
    public $incrementing = false;
    protected $primaryKey = null;
    
    // ✅ ADD keterangan to fillable
    protected $fillable = ['unique_id', 'id_barang', 'jumlah_barang', 'keterangan'];
    
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

    /**
     * Scope a query to only include Gudang records a user is allowed to see.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query; // Admin sees all.
        }

        if ($user->hasRole('manager')) {
            return $query->whereHas('user', function ($q) use ($user) {
                $q->where('branch_name', $user->branch_name);
            });
        }

        // Default to a regular user who can only see their own stock.
        return $query->where('unique_id', $user->unique_id);
    }
}
