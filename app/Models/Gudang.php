<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Gudang extends Pivot
{
    protected $table = 'tb_gudang';

    /**
     * ✅ FIX: For composite keys, we can't use primaryKey = null.
     * Instead, we disable incrementing and don't define a single primaryKey.
     * Laravel will handle composite keys properly in queries if we use
     * where() clauses explicitly or override getKeyName() to return an array.
     */
    public $incrementing = false;
    
    // Don't set primaryKey to null - it breaks firstOrNew and updates
    // Leave it undefined or set to the first key column
    protected $primaryKey = 'unique_id';
    
    // Since this is a composite key, we need to tell Laravel the key type
    protected $keyType = 'string';

    protected $fillable = [
        'unique_id',
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
        $query->where('unique_id', $this->getAttribute('unique_id'))
              ->where('id_barang', $this->getAttribute('id_barang'));
        
        return $query;
    }

    // --- RELATIONSHIPS ---
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unique_id', 'unique_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    // --- SCOPES ---
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
