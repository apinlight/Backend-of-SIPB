<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Pengajuan extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'Menunggu Persetujuan';

    const STATUS_APPROVED = 'Disetujui';

    const STATUS_REJECTED = 'Ditolak';

    const STATUS_COMPLETED = 'Selesai';

    const STATUS_DRAFT = 'Draft';

    protected $table = 'tb_pengajuan';

    protected $primaryKey = 'id_pengajuan';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id_pengajuan', 'unique_id', 'status_pengajuan', 'tipe_pengajuan', 'bukti_file',
        'approved_by', 'approved_at', 'rejected_by', 'rejected_at',
        'rejection_reason', 'approval_notes', 'keterangan',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unique_id', 'unique_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailPengajuan::class, 'id_pengajuan', 'id_pengajuan');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'unique_id');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by', 'unique_id');
    }

    protected function buktiFileUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->bukti_file ? Storage::url($this->bukti_file) : null);
    }

    public function isMutable(): bool
    {
        return in_array($this->status_pengajuan, [self::STATUS_PENDING, self::STATUS_DRAFT]);
    }

    public function canBeDeleted(): bool
    {
        return in_array($this->status_pengajuan, [self::STATUS_PENDING, self::STATUS_REJECTED]);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status_pengajuan', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status_pengajuan', self::STATUS_APPROVED);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status_pengajuan', self::STATUS_REJECTED);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status_pengajuan', self::STATUS_COMPLETED);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }
        if ($user->hasRole('manager')) {
            return $query->whereHas('user', fn ($q) => $q->where('branch_name', $user->branch_name));
        }

        return $query->where('unique_id', $user->unique_id);
    }
}
