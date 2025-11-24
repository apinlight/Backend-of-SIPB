<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gudang extends Model
{
    protected $table = 'tb_gudang';

    /**
     * ✅ FIX: For composite keys, we can't use primaryKey = null.
     * Instead, we disable incrementing and don't define a single primaryKey.
     * Laravel will handle composite keys properly in queries if we use
     * where() clauses explicitly or override getKeyName() to return an array.
     */
    public $incrementing = false;

    protected $primaryKey = 'id_cabang';

    protected $keyType = 'string';

    protected $fillable = [
        'id_cabang',
        'id_barang',
        'jumlah_barang',
        'keterangan',
        'tipe',
    ];

    /**
     * Set the keys for a save/update query.
     * Override to handle composite primary key properly.
     */
    protected function setKeysForSaveQuery($query)
    {
        $query->where('id_cabang', $this->getAttribute('id_cabang'))
              ->where('id_barang', $this->getAttribute('id_barang'));
        
        return $query;
    }

    // --- RELATIONSHIPS ---
    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang', 'id_cabang');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    // --- SCOPES ---
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            return $query; // Admin/Manager can see all for monitoring
        }

        // Regular user can only see their branch stock
        return $query->where('id_cabang', $user->id_cabang);
    }
}
