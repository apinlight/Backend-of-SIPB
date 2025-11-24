<?php

namespace App\Services;

use App\Models\Gudang;
use App\Models\Pengajuan;
use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PengajuanService
{
    protected $settingsService;

    public function __construct(GlobalSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function create(array $data, ?UploadedFile $file): Pengajuan
    {
        $this->validateMonthlyLimit($data['unique_id']);
        if ($file && ($data['tipe_pengajuan'] ?? 'biasa') === 'mandiri') {
            $data['bukti_file'] = $file->store('bukti-pengajuan', 'public');
        }
        // Force initial status to pending regardless of payload
        $data['status_pengajuan'] = Pengajuan::STATUS_PENDING;
        $pengajuan = Pengajuan::create($data);
        if (!empty($data['items'])) {
            $pengajuan->details()->createMany($data['items']);
            // eager load details for immediate API resource serialization
            $pengajuan->load(['details.barang']);
        }
        return $pengajuan;
    }

    public function updateStatus(Pengajuan $pengajuan, User $approver, array $data): Pengajuan
    {
        $newStatus = $data['status_pengajuan'];

        if ($newStatus === Pengajuan::STATUS_APPROVED) {
            $stockErrors = $this->validateStockLimitsOnApproval($pengajuan, $approver);
            if (! empty($stockErrors)) {
                throw new Exception(json_encode($stockErrors));
            }
        }

        $this->updateStatusAndAudit($pengajuan, $approver, $newStatus, $data);

        if ($newStatus === Pengajuan::STATUS_APPROVED && in_array($pengajuan->tipe_pengajuan, ['biasa', 'manual'])) {
            $this->transferStock($pengajuan, $approver);
        }

        return $pengajuan->fresh(['user', 'details.barang', 'approver', 'rejector']);
    }

    public function delete(Pengajuan $pengajuan): void
    {
        if (! $pengajuan->canBeDeleted()) {
            throw new Exception('Cannot delete an approved or completed pengajuan.');
        }
        if ($pengajuan->bukti_file && Storage::disk('public')->exists($pengajuan->bukti_file)) {
            Storage::disk('public')->delete($pengajuan->bukti_file);
        }
        $pengajuan->delete();
    }

    private function validateMonthlyLimit(string $userId): void
    {
        $user = User::find($userId);
        if (! $user || $user->hasRole(\App\Enums\Role::ADMIN)) {
            return;
        }
        $monthlyLimit = $this->settingsService->getMonthlyLimit();
        $currentMonthCount = Pengajuan::where('unique_id', $userId)
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
            ->whereIn('status_pengajuan', [Pengajuan::STATUS_PENDING, Pengajuan::STATUS_APPROVED])->count();
        if ($currentMonthCount >= $monthlyLimit) {
            throw new Exception(json_encode(['monthly_limit' => "Monthly submission limit of {$monthlyLimit} has been reached."]));
        }
    }

    private function validateStockLimitsOnApproval(Pengajuan $pengajuan, User $approver): array
    {
        $errors = [];
        foreach ($pengajuan->details as $detail) {
            $sourceStock = Gudang::where('id_cabang', $approver->id_cabang)
                ->where('id_barang', $detail->id_barang)->value('jumlah_barang') ?? 0;
            if ($sourceStock < $detail->jumlah) {
                $errors[] = "Insufficient central stock for item {$detail->barang->nama_barang}. Available: {$sourceStock}, Requested: {$detail->jumlah}";
            }
        }

        return $errors;
    }

    private function updateStatusAndAudit(Pengajuan $pengajuan, User $user, string $status, array $data): void
    {
        $updateData = ['status_pengajuan' => $status];
        if ($status === Pengajuan::STATUS_APPROVED) {
            $updateData['approved_at'] = now();
            $updateData['approved_by'] = $user->unique_id;
            $updateData['approval_notes'] = $data['approval_notes'] ?? null;
        } elseif ($status === Pengajuan::STATUS_REJECTED) {
            $updateData['rejected_at'] = now();
            $updateData['rejected_by'] = $user->unique_id;
            $updateData['rejection_reason'] = $data['rejection_reason'] ?? null;
        }
        $pengajuan->update($updateData);
    }

    public function checkStockAvailability(Pengajuan $pengajuan, User $approver): array
    {
        $stockStatus = [
            'available' => true,
            'details' => [],
            'total_items' => $pengajuan->details->count()
        ];

        foreach ($pengajuan->details as $detail) {
            $sourceStock = Gudang::where('id_cabang', $approver->id_cabang)
                ->where('id_barang', $detail->id_barang)
                ->value('jumlah_barang') ?? 0;

            $itemStatus = [
                'id_barang' => $detail->id_barang,
                'nama_barang' => $detail->barang->nama_barang,
                'requested' => $detail->jumlah,
                'available' => $sourceStock,
                'sufficient' => $sourceStock >= $detail->jumlah,
                'shortage' => max(0, $detail->jumlah - $sourceStock)
            ];

            if (!$itemStatus['sufficient']) {
                $stockStatus['available'] = false;
            }

            $stockStatus['details'][] = $itemStatus;
        }

        return $stockStatus;
    }

    protected function transferStock(Pengajuan $pengajuan, User $approver): void
    {
        DB::transaction(function () use ($pengajuan, $approver) {
            foreach ($pengajuan->details as $detail) {
                // ✅ PERBAIKAN FINAL: Buat query dengan composite key untuk memperbarui sumber.
                $updatedRows = Gudang::where('id_cabang', $approver->id_cabang)
                    ->where('id_barang', $detail->id_barang)
                    ->decrement('jumlah_barang', $detail->jumlah);

                // Ini adalah pengaman penting. Jika baris tidak ada atau decrement gagal,
                // kita lempar error.
                if ($updatedRows === 0) {
                    throw new Exception("Gagal mengurangi stok untuk item {$detail->id_barang}. Stok sumber mungkin tidak ada atau tidak mencukupi.");
                }

                // ✅ PERBAIKAN KEAMANAN: Hindari SQL injection dengan validasi input dan parameter binding
                $jumlahToAdd = (int) $detail->jumlah; // Cast to integer untuk keamanan

                // Cek apakah record sudah ada
                $requester = $pengajuan->user;
                $existingRecord = Gudang::where('id_cabang', $requester->id_cabang)
                    ->where('id_barang', $detail->id_barang)
                    ->first();

                if ($existingRecord) {
                    // Jika ada, gunakan increment yang aman
                    $existingRecord->increment('jumlah_barang', $jumlahToAdd);
                } else {
                    // Jika belum ada, buat baru dengan jumlah yang divalidasi
                    Gudang::create([
                        'id_cabang' => $requester->id_cabang,
                        'id_barang' => $detail->id_barang,
                        'jumlah_barang' => $jumlahToAdd,
                    ]);
                }
            }
        });
    }

    /**
     * Get comprehensive info for the pengajuan form including stock, limits, and monthly usage
     */
    public function getInfoForForm(User $user, array $filters = []): object
    {
        $barang = \App\Models\Barang::with(['jenisBarang', 'batasBarang'])
            ->whereHas('jenisBarang', function ($q) {
                $q->where('is_active', true);
            })
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $q->where('nama_barang', 'like', "%{$filters['search']}%");
            })
            ->get();

        // Get user's current stock
        $userStock = Gudang::where('id_cabang', $user->id_cabang)
            ->pluck('jumlah_barang', 'id_barang')
            ->toArray();

        // Get admin stock (from central warehouse marked as is_pusat)
        $adminStock = Gudang::whereHas('cabang', function ($q) {
            $q->where('is_pusat', true);
        })
        ->selectRaw('id_barang, SUM(jumlah_barang) as total')
        ->groupBy('id_barang')
        ->pluck('total', 'id_barang')
        ->toArray();

        // Get monthly info
        $monthlyLimit = $this->settingsService->getMonthlyLimit();
        $pengajuanCount = Pengajuan::where('unique_id', $user->unique_id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $pendingCount = Pengajuan::where('unique_id', $user->unique_id)
            ->where('status_pengajuan', Pengajuan::STATUS_PENDING)
            ->count();

        // Add stock info to each barang item
        $barangWithInfo = $barang->map(function ($item) use ($userStock, $adminStock) {
            $item->stock_info = [
                'user_stock' => $userStock[$item->id_barang] ?? 0,
                'admin_stock' => $adminStock[$item->id_barang] ?? 0,
            ];
            return $item;
        });

        return (object) [
            'barang' => $barangWithInfo,
            'user_info' => [
                'unique_id' => $user->unique_id,
                'username' => $user->username,
                'id_cabang' => $user->id_cabang,
            ],
            'monthly_info' => [
                'limit' => $monthlyLimit,
                'used' => $pengajuanCount,
                'pending' => $pendingCount,
            ],
        ];
    }

    /**
     * Get historical pengajuan data for a specific item
     */
    public function getItemHistory(User $user, string $idBarang, int $months = 6): array
    {
        $history = Pengajuan::where('unique_id', $user->unique_id)
            ->with(['details' => function ($q) use ($idBarang) {
                $q->where('id_barang', $idBarang);
            }])
            ->where('created_at', '>=', now()->subMonths($months))
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(fn ($p) => $p->details->isNotEmpty())
            ->map(function ($pengajuan) {
                return [
                    'id_pengajuan' => $pengajuan->id_pengajuan,
                    'tanggal' => $pengajuan->created_at->format('Y-m-d'),
                    'status' => $pengajuan->status_pengajuan,
                    'jumlah' => $pengajuan->details->first()->jumlah,
                ];
            })
            ->values()
            ->toArray();

        return $history;
    }
}
