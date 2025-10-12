<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenggunaanBarang extends Model
{
    protected $table = 'tb_penggunaan_barang';

    protected $primaryKey = 'id_penggunaan';

    protected $fillable = [
        'unique_id',
        'id_barang',
        'jumlah_digunakan',
        'keperluan',
        'tanggal_penggunaan',
        'keterangan',
        'approved_by',
        'approved_at',
        'status',
    ];

    protected $casts = [
        'tanggal_penggunaan' => 'date',
        'approved_at' => 'datetime',
        'jumlah_digunakan' => 'integer',
    ];

    // ✅ Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unique_id', 'unique_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'unique_id');
    }

    // ✅ Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query; // Admin can see all
        }

        if ($user->hasRole('manager')) {
            return $query->whereHas('user', function ($q) use ($user) {
                $q->where('branch_name', $user->branch_name);
            });
        }

        // Regular user can only see their own
        return $query->where('unique_id', $user->unique_id);
    }
}
