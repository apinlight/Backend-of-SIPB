<?php
// app/Models/Pengajuan.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Pengajuan extends Model
{
    protected $table = 'tb_pengajuan';
    protected $primaryKey = 'id_pengajuan';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id_pengajuan',
        'unique_id',
        'status_pengajuan',
        'tipe_pengajuan',
        'bukti_file',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'approval_notes',
        'keterangan',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // ✅ Relationships
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

    // ✅ Business Logic Methods
    public function updateStatus(string $status, array $additionalData = []): void
    {
        $updateData = ['status_pengajuan' => $status];
               
        match($status) {
            'Disetujui' => $updateData = array_merge($updateData, [
                'approved_at' => now(),
                'approved_by' => Auth::user()->unique_id,
                'approval_notes' => $additionalData['approval_notes'] ?? null,
            ]),
            'Ditolak' => $updateData = array_merge($updateData, [
                'rejected_at' => now(),
                'rejected_by' => Auth::user()->unique_id,
                'rejection_reason' => $additionalData['rejection_reason'] ?? null,
            ]),
            default => null
        };

        $this->update($updateData);
    }

    public function canBeDeleted(): bool
    {
        return in_array($this->status_pengajuan, ['Menunggu Persetujuan', 'Ditolak']);
    }

    public function canBeApproved(): bool
    {
        return $this->status_pengajuan === 'Menunggu Persetujuan';
    }

    public function getBuktiFileUrl(): ?string
    {
        return $this->bukti_file ? Storage::url($this->bukti_file) : null;
    }

    public function getTotalValue(): float
    {
        return $this->details->sum(function($detail) {
            return ($detail->barang->harga_barang ?? 0) * $detail->jumlah;
        });
    }

    // ✅ Validation Methods
    public static function validateMonthlyLimit(string $uniqueId): bool
    {
        $user = User::where('unique_id', $uniqueId)->first();
               
        // Admin has no monthly limit
        if ($user && $user->hasRole('admin')) {
            return true;
        }

        $monthlyLimit = GlobalSetting::getMonthlyPengajuanLimit();
        $currentMonthCount = self::where('unique_id', $uniqueId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereIn('status_pengajuan', ['Menunggu Persetujuan', 'Disetujui']) // ✅ Count both pending and approved
            ->count();

        return $currentMonthCount < $monthlyLimit;
    }

    public function validateStockLimits(): array
    {
        $errors = [];
               
        foreach ($this->details as $detail) {
            $currentStock = Gudang::where('unique_id', $this->unique_id)
                ->where('id_barang', $detail->id_barang)
                ->value('jumlah_barang') ?? 0;
                           
            $batasBarang = BatasBarang::where('id_barang', $detail->id_barang)
                ->value('batas_barang') ?? PHP_INT_MAX;
                           
            $newTotal = $currentStock + $detail->jumlah;
                       
            if ($newTotal > $batasBarang) {
                $errors[] = [
                    'barang' => $detail->barang->nama_barang,
                    'current_stock' => $currentStock,
                    'requested' => $detail->jumlah,
                    'max_allowed' => $batasBarang,
                    'message' => "Stock akan melebihi batas ({$newTotal} > {$batasBarang})"
                ];
            }
        }
               
        return $errors;
    }

    // ✅ Scopes for filtering
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status_pengajuan', 'Menunggu Persetujuan');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status_pengajuan', 'Disetujui');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status_pengajuan', 'Ditolak');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status_pengajuan', 'Selesai');
    }

    public function scopeRegular(Builder $query): Builder
    {
        return $query->where('tipe_pengajuan', 'biasa');
    }

    public function scopeManual(Builder $query): Builder
    {
        return $query->where('tipe_pengajuan', 'manual');
    }

    // ✅ Scope for role-based access
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
