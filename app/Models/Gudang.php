<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gudang extends Pivot
{
    protected $table = 'tb_gudang';

    /**
     * ✅ FIX: A pivot model with a composite key MUST have incrementing set to false
     * and should NOT have a primaryKey property defined, as its key is the combination
     * of its foreign keys.
     */
    public $incrementing = false;

    protected $fillable = [
        'unique_id',
        'id_barang',
        'jumlah_barang',
        'keterangan',
        'tipe',
    ];

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