<?php
// app/Models/PenggunaanBarang.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id_penggunaan
 * @property string $unique_id
 * @property string $id_barang
 * @property int $jumlah_digunakan
 * @property string $keperluan
 * @property \Illuminate\Support\Carbon $tanggal_penggunaan
 * @property string|null $keterangan
 * @property string|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Barang $barang
 * @property-read \App\Models\User|null $approver
 */
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
        'status'
    ];

    protected $casts = [
        'tanggal_penggunaan' => 'date',
        'approved_at' => 'datetime',
        'jumlah_digunakan' => 'integer'
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

    // ✅ Business Logic
    public function approve(string $approverUniqueId): bool
    {
        // Check if there's enough stock
        $currentStock = Gudang::where('unique_id', $this->unique_id)
            ->where('id_barang', $this->id_barang)
            ->value('jumlah_barang') ?? 0;

        if ($currentStock < $this->jumlah_digunakan) {
            return false; // Not enough stock
        }

        // Update usage status
        $this->update([
            'status' => 'approved',
            'approved_by' => $approverUniqueId,
            'approved_at' => now()
        ]);

        // Reduce stock from gudang
        $this->reduceStockFromGudang();

        return true;
    }

    public function reject(string $approverUniqueId): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approverUniqueId,
            'approved_at' => now()
        ]);
    }

    private function reduceStockFromGudang(): void
    {
        $gudangRecord = Gudang::where('unique_id', $this->unique_id)
            ->where('id_barang', $this->id_barang)
            ->first();

        if ($gudangRecord) {
            $newStock = $gudangRecord->jumlah_barang - $this->jumlah_digunakan;
            
            if ($newStock <= 0) {
                // Remove record if stock becomes 0 or negative
                $gudangRecord->delete();
            } else {
                // Update stock
                $gudangRecord->update(['jumlah_barang' => $newStock]);
            }
        }
    }

    // ✅ Validation
    public function validateStock(): bool
    {
        $currentStock = Gudang::where('unique_id', $this->unique_id)
            ->where('id_barang', $this->id_barang)
            ->value('jumlah_barang') ?? 0;

        return $currentStock >= $this->jumlah_digunakan;
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
            return $query->whereHas('user', function($q) use ($user) {
                $q->where('branch_name', $user->branch_name);
            });
        }
        
        // Regular user can only see their own
        return $query->where('unique_id', $user->unique_id);
    }
}
