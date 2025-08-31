<?php
// app/Http/Controllers/Api/PenggunaanBarangController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PenggunaanBarangResource;
use App\Models\PenggunaanBarang;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PenggunaanBarangController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = PenggunaanBarang::with(['user', 'barang.jenisBarang', 'approver'])
                ->forUser(Auth::user());

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('id_barang')) {
                $query->where('id_barang', $request->id_barang);
            }

            if ($request->filled('tanggal_dari')) {
                $query->whereDate('tanggal_penggunaan', '>=', $request->tanggal_dari);
            }

            if ($request->filled('tanggal_sampai')) {
                $query->whereDate('tanggal_penggunaan', '<=', $request->tanggal_sampai);
            }

            if ($request->filled('search')) {
                $query->whereHas('barang', function($q) use ($request) {
                    $q->where('nama_barang', 'like', '%' . $request->search . '%');
                })->orWhere('keperluan', 'like', '%' . $request->search . '%');
            }

            $penggunaan = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return PenggunaanBarangResource::collection($penggunaan)
                ->additional([
                    'success' => true,
                    'message' => 'Data penggunaan barang berhasil diambil'
                ])
                ->response();

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data penggunaan barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_barang' => 'required|exists:tb_barang,id_barang',
                'jumlah_digunakan' => 'required|integer|min:1',
                'keperluan' => 'required|string|max:255',
                'tanggal_penggunaan' => 'required|date',
                'keterangan' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Check current stock with lock
                $gudangRecord = Gudang::where('unique_id', Auth::user()->unique_id)
                    ->where('id_barang', $request->id_barang)
                    ->lockForUpdate()
                    ->first();

                $currentStock = $gudangRecord ? $gudangRecord->jumlah_barang : 0;

                if ($currentStock < $request->jumlah_digunakan) {
                    throw new \Exception("Stok tidak mencukupi. Tersedia: {$currentStock}, Diminta: {$request->jumlah_digunakan}");
                }

                // Create usage record
                $penggunaan = PenggunaanBarang::create([
                    'unique_id' => Auth::user()->unique_id,
                    'id_barang' => $request->id_barang,
                    'jumlah_digunakan' => $request->jumlah_digunakan,
                    'keperluan' => $request->keperluan,
                    'tanggal_penggunaan' => $request->tanggal_penggunaan,
                    'keterangan' => $request->keterangan,
                    'status' => 'approved', // Auto-approve for now
                    'approved_by' => Auth::user()->unique_id,
                    'approved_at' => now()
                ]);

                // Reduce stock atomically
                if ($gudangRecord) {
                    $newStock = $gudangRecord->jumlah_barang - $request->jumlah_digunakan;
                    
                    if ($newStock <= 0) {
                        $gudangRecord->delete();
                    } else {
                        $gudangRecord->update(['jumlah_barang' => $newStock]);
                    }
                }

                DB::commit();

                $penggunaan->load(['user', 'barang.jenisBarang']);

                return (new PenggunaanBarangResource($penggunaan))
                    ->additional([
                        'success' => true,
                        'message' => 'Penggunaan barang berhasil dicatat'
                    ])
                    ->response()
                    ->setStatusCode(201);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencatat penggunaan barang: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $penggunaan = PenggunaanBarang::with(['user', 'barang.jenisBarang', 'approver'])
                ->forUser(Auth::user())
                ->findOrFail($id);

            return (new PenggunaanBarangResource($penggunaan))
                ->additional([
                    'success' => true,
                    'message' => 'Detail penggunaan barang berhasil diambil'
                ])
                ->response();

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail penggunaan barang',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $penggunaan = PenggunaanBarang::forUser(Auth::user())->findOrFail($id);

            // Only allow updates if status is pending
            if ($penggunaan->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Penggunaan barang yang sudah disetujui/ditolak tidak dapat diubah'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'jumlah_digunakan' => 'sometimes|integer|min:1',
                'keperluan' => 'sometimes|string|max:255',
                'tanggal_penggunaan' => 'sometimes|date',
                'keterangan' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $penggunaan->update($request->only([
                'jumlah_digunakan', 'keperluan', 'tanggal_penggunaan', 'keterangan'
            ]));

            $penggunaan->load(['user', 'barang.jenisBarang', 'approver']);

            return (new PenggunaanBarangResource($penggunaan))
                ->additional([
                    'success' => true,
                    'message' => 'Penggunaan barang berhasil diperbarui'
                ])
                ->response();

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui penggunaan barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $penggunaan = PenggunaanBarang::forUser(Auth::user())->findOrFail($id);

            // Only allow deletion if status is pending or rejected
            if ($penggunaan->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Penggunaan barang yang sudah disetujui tidak dapat dihapus'
                ], 400);
            }

            $penggunaan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Penggunaan barang berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus penggunaan barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->hasRole(['admin', 'manager'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak memiliki akses untuk menyetujui penggunaan barang'
                ], 403);
            }

            DB::beginTransaction();

            try {
                $penggunaan = PenggunaanBarang::with(['user', 'barang'])
                    ->forUser($user)
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ($penggunaan->status !== 'pending') {
                    throw new \Exception('Penggunaan barang sudah diproses sebelumnya');
                }

                // Check stock availability
                $gudangRecord = Gudang::where('unique_id', $penggunaan->unique_id)
                    ->where('id_barang', $penggunaan->id_barang)
                    ->lockForUpdate()
                    ->first();

                $currentStock = $gudangRecord ? $gudangRecord->jumlah_barang : 0;

                if ($currentStock < $penggunaan->jumlah_digunakan) {
                    throw new \Exception("Stok tidak mencukupi untuk approval. Tersedia: {$currentStock}");
                }

                // Update penggunaan status
                $penggunaan->update([
                    'status' => 'approved',
                    'approved_by' => $user->unique_id,
                    'approved_at' => now()
                ]);

                // Reduce stock
                if ($gudangRecord) {
                    $newStock = $gudangRecord->jumlah_barang - $penggunaan->jumlah_digunakan;
                    
                    if ($newStock <= 0) {
                        $gudangRecord->delete();
                    } else {
                        $gudangRecord->update(['jumlah_barang' => $newStock]);
                    }
                }

                DB::commit();

                $penggunaan->load(['user', 'barang.jenisBarang', 'approver']);

                return (new PenggunaanBarangResource($penggunaan))
                    ->additional([
                        'success' => true,
                        'message' => 'Penggunaan barang berhasil disetujui'
                    ])
                    ->response();

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyetujui penggunaan barang: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->hasRole(['admin', 'manager'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak memiliki akses untuk menolak penggunaan barang'
                ], 403);
            }

            $penggunaan = PenggunaanBarang::with(['user', 'barang.jenisBarang'])
                ->forUser($user)
                ->findOrFail($id);

            if ($penggunaan->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Penggunaan barang sudah diproses sebelumnya'
                ], 400);
            }

            $penggunaan->update([
                'status' => 'rejected',
                'approved_by' => $user->unique_id,
                'approved_at' => now(),
                'keterangan' => $penggunaan->keterangan . ' | Ditolak: ' . ($request->rejection_reason ?? 'Tidak ada alasan')
            ]);

            $penggunaan->load(['approver']);

            return (new PenggunaanBarangResource($penggunaan))
                ->additional([
                    'success' => true,
                    'message' => 'Penggunaan barang berhasil ditolak'
                ])
                ->response();

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menolak penggunaan barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ FIXED: Get all available stock for current user (no required parameters)
    public function getAvailableStock(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // ✅ Build query for available stock based on user role
            $query = Gudang::with(['barang.jenisBarang', 'user'])
                ->where('jumlah_barang', '>', 0);
            
            // ✅ Apply user scope filtering
            if ($user->hasRole('admin')) {
                // Admin can see all stock across all branches
                // No additional filtering needed
            } elseif ($user->hasRole('manager')) {
                // Manager can see stock for their branch only
                $query->whereHas('user', function($q) use ($user) {
                    $q->where('branch_name', $user->branch_name);
                });
            } else {
                // Regular user can only see their own stock
                $query->where('unique_id', $user->unique_id);
            }
            
            // ✅ Optional filter by specific item (if provided)
            if ($request->filled('id_barang')) {
                $query->where('id_barang', $request->id_barang);
            }
            
            // ✅ Optional search filter
            if ($request->filled('search')) {
                $query->whereHas('barang', function($q) use ($request) {
                    $q->where('nama_barang', 'like', '%' . $request->search . '%');
                });
            }
            
            // ✅ Get the stock data and transform it
            $stockData = $query->get()->map(function($gudang) {
                return [
                    'id_barang' => $gudang->id_barang,
                    'nama_barang' => $gudang->barang?->nama_barang ?? 'Unknown Item',
                    'jenis_barang' => $gudang->barang?->jenisBarang?->nama_jenis_barang ?? 'Unknown Category',
                    'jumlah_tersedia' => $gudang->jumlah_barang,
                    'harga_satuan' => $gudang->barang?->harga_barang ?? 0,
                    'total_nilai' => ($gudang->barang?->harga_barang ?? 0) * $gudang->jumlah_barang,
                    'user_info' => [
                        'unique_id' => $gudang->user?->unique_id,
                        'username' => $gudang->user?->username,
                        'branch_name' => $gudang->user?->branch_name,
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Data stok tersedia berhasil diambil',
                'data' => $stockData,
                'meta' => [
                    'total_items' => $stockData->count(),
                    'total_stock' => $stockData->sum('jumlah_tersedia'),
                    'total_value' => $stockData->sum('total_nilai'),
                    'user_role' => $user->getRoleNames()->first(),
                    'user_branch' => $user->branch_name
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data stok tersedia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ Get pending approvals (for admin/manager)
    public function pendingApprovals(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user->hasRole(['admin', 'manager'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak memiliki akses untuk melihat pending approvals'
                ], 403);
            }

            $query = PenggunaanBarang::with(['user', 'barang.jenisBarang'])
                ->where('status', 'pending')
                ->forUser($user);

            $pendingApprovals = $query->orderBy('created_at', 'asc')
                ->paginate($request->get('per_page', 15));

            return PenggunaanBarangResource::collection($pendingApprovals)
                ->additional([
                    'success' => true,
                    'message' => 'Data pending approvals berhasil diambil'
                ])
                ->response();

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pending approvals',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ BONUS: Get stock for specific item (helper method)
    public function getStockForItem(Request $request, string $id_barang): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $gudangRecord = Gudang::with(['barang.jenisBarang'])
                ->where('id_barang', $id_barang)
                ->where('unique_id', $user->unique_id)
                ->first();

            $availableStock = $gudangRecord ? $gudangRecord->jumlah_barang : 0;

            return response()->json([
                'success' => true,
                'message' => 'Data stok barang berhasil diambil',
                'data' => [
                    'id_barang' => $id_barang,
                    'nama_barang' => $gudangRecord?->barang?->nama_barang ?? 'Item not found',
                    'jenis_barang' => $gudangRecord?->barang?->jenisBarang?->nama_jenis_barang ?? 'Unknown',
                    'jumlah_tersedia' => $availableStock,
                    'harga_satuan' => $gudangRecord?->barang?->harga_barang ?? 0,
                    'has_stock' => $availableStock > 0,
                    'can_use' => $availableStock > 0
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data stok barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
