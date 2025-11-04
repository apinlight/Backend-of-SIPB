<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePenggunaanBarangRequest;
use App\Http\Resources\PenggunaanBarangResource;
use App\Models\PenggunaanBarang;
use App\Services\PenggunaanBarangService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PenggunaanBarangController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected PenggunaanBarangService $penggunaanBarangService)
    {
        // ❌ REMOVE THIS to prevent the 500 error
        // $this->authorizeResource(PenggunaanBarang::class, 'penggunaan_barang');
    }

    public function index(Request $request): JsonResponse
    {
        // ✅ ADD manual authorization
        $this->authorize('viewAny', PenggunaanBarang::class);

        $query = PenggunaanBarang::with(['user', 'barang.jenisBarang', 'approver'])
            ->forUser($request->user());

        $query->when($request->filled('status'), fn ($q) => $q->where('status', 'like', "%{$request->status}%"));
        // ... other filters ...

        $penggunaan = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return PenggunaanBarangResource::collection($penggunaan)->response();
    }

    public function store(StorePenggunaanBarangRequest $request): JsonResponse
    {
        // ✅ ADD manual authorization
        $this->authorize('create', PenggunaanBarang::class);

        $penggunaan = $this->penggunaanBarangService->recordUsage(
            $request->user(),
            $request->validated()
        );

        return (new PenggunaanBarangResource($penggunaan->load(['user', 'barang.jenisBarang'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(PenggunaanBarang $penggunaan_barang): JsonResponse
    {
        // ✅ ADD manual authorization
        $this->authorize('view', $penggunaan_barang);

        return (new PenggunaanBarangResource($penggunaan_barang->load(['user', 'barang.jenisBarang', 'approver'])))
            ->response();
    }

    public function update(Request $request, PenggunaanBarang $penggunaan_barang): JsonResponse
    {
        // ✅ ADD manual authorization
        $this->authorize('update', $penggunaan_barang);

        $penggunaan_barang->update($request->all());

        return (new PenggunaanBarangResource($penggunaan_barang))->response();
    }

    public function destroy(PenggunaanBarang $penggunaan_barang): JsonResponse
    {
        // ✅ ADD manual authorization
        $this->authorize('delete', $penggunaan_barang);

        $penggunaan_barang->delete();

        return response()->json(null, 204);
    }

    /**
     * ✅ NEW: Get available stock for all items (filtered by user role/scope)
     */
    public function getAvailableStock(Request $request): JsonResponse
    {
        $user = $request->user();

        // Query gudang with barang relation
        $query = \App\Models\Gudang::with('barang.jenisBarang');

        // ✅ Scope by role: user sees only their area, admin/manager see all
        if ($user->hasRole('user')) {
            $query->where('unique_id', $user->unique_id);
        }

        // ✅ FIX: Group by id_barang and sum stock across unique_id for admin/manager
        // For regular users, they only see their own stock anyway
        $stok = $query->get()
            ->groupBy('id_barang')
            ->map(function ($items) use ($user) {
                // For users, return single stock entry (their own)
                // For admin/manager, sum all stock entries for this item
                $firstItem = $items->first();
                $totalStock = $items->sum('jumlah_barang');
                
                // ✅ FIX: Access nested relation safely with null checks
                $barang = $firstItem->barang;
                $jenisBarang = $barang ? $barang->jenisBarang : null;
                
                return [
                    'id_barang' => $firstItem->id_barang,
                    'nama_barang' => $barang->nama_barang ?? 'Unknown',
                    'jenis_barang' => $jenisBarang->nama_jenis_barang ?? 'N/A', // ✅ FIX: nama_jenis_barang not nama_jenis
                    'jumlah_tersedia' => (int) $totalStock,
                    'unique_id' => $user->hasRole('user') ? $firstItem->unique_id : null, // Only for users
                ];
            })
            ->values();

        return response()->json([
            'status' => true,
            'data' => $stok
        ]);
    }

    /**
     * ✅ NEW: Get available stock for a specific item (filtered by user role/scope)
     */
    public function getStockForItem(Request $request, string $id_barang): JsonResponse
    {
        $user = $request->user();

        $query = \App\Models\Gudang::where('id_barang', $id_barang)->with('barang.jenisBarang');

        // ✅ Scope by role
        if ($user->hasRole('user')) {
            $query->where('unique_id', $user->unique_id);
        }

        $stok = $query->first();

        if (! $stok) {
            return response()->json([
                'status' => false,
                'message' => 'Stok tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id_barang' => $stok->id_barang,
                'nama_barang' => $stok->barang->nama_barang ?? 'Unknown',
                'jenis_barang' => $stok->barang->jenisBarang->nama_jenis_barang ?? 'N/A', // ✅ FIX: nama_jenis_barang not nama_jenis
                'jumlah_tersedia' => (int) $stok->jumlah_barang,
                'unique_id' => $stok->unique_id,
            ]
        ]);
    }
}
