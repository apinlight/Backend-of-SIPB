<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BarangResource;
use App\Models\Barang;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BarangController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        // ✅ All authenticated users can view barang list
        $this->authorize('viewAny', Barang::class);
        
        $query = Barang::with(['jenisBarang']);

        // ✅ Apply filters
        if ($request->filled('search')) {
            $query->where('nama_barang', 'like', "%{$request->search}%");
        }

        if ($request->filled('jenis')) {
            $query->where('id_jenis_barang', $request->jenis);
        }

        if ($request->filled('harga_min')) {
            $query->where('harga_barang', '>=', $request->harga_min);
        }

        if ($request->filled('harga_max')) {
            $query->where('harga_barang', '<=', $request->harga_max);
        }

        $barang = $query->paginate(20);
        return BarangResource::collection($barang);
    }

    public function store(Request $request)
    {
        // ✅ Only admin can create barang
        $this->authorize('create', Barang::class);
        
        $data = $request->validate([
            'id_barang'        => 'required|string|unique:tb_barang,id_barang',
            'id_jenis_barang'  => 'required|string|exists:tb_jenis_barang,id_jenis_barang',
            'nama_barang'      => 'required|string|max:255',
            'harga_barang'     => 'required|numeric|min:0',
            'deskripsi'        => 'nullable|string|max:1000',
            'satuan'           => 'nullable|string|max:50',
            'batas_minimum'    => 'sometimes|integer|min:0',
        ]);

        $data['batas_minimum'] = $data['batas_minimum'] ?? 5;

        $barang = Barang::create($data);
        return (new BarangResource($barang->load('jenisBarang')))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show($id_barang)
    {
        $barang = Barang::with(['jenisBarang'])->where('id_barang', $id_barang)->firstOrFail();
        $this->authorize('view', $barang);
        
        return new BarangResource($barang);
    }

    public function update(Request $request, $id_barang)
    {
        $barang = Barang::where('id_barang', $id_barang)->firstOrFail();
        $this->authorize('update', $barang);

        $data = $request->validate([
            'id_jenis_barang'  => 'sometimes|required|string|exists:tb_jenis_barang,id_jenis_barang',
            'nama_barang'      => 'sometimes|required|string|max:255',
            'harga_barang'     => 'sometimes|required|numeric|min:0',
            'deskripsi'        => 'nullable|string|max:1000',
            'satuan'           => 'nullable|string|max:50',
            'batas_minimum'    => 'sometimes|integer|min:0',
        ]);

        $barang->update($data);
        return new BarangResource($barang->fresh(['jenisBarang']));
    }

    public function destroy($id_barang)
    {
        $barang = Barang::where('id_barang', $id_barang)->firstOrFail();
        $this->authorize('delete', $barang);

        // ✅ Check if barang is used in active pengajuan
        $activePengajuan = $barang->detailPengajuan()
            ->whereHas('pengajuan', fn($q) => 
                $q->whereIn('status_pengajuan', ['Menunggu Persetujuan', 'Disetujui'])
            )->count();

        if ($activePengajuan > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete barang with active pengajuan'
            ], 422);
        }

        // ✅ Check if barang exists in gudang
        $gudangCount = $barang->gudang()->sum('jumlah_barang');
        if ($gudangCount > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete barang that exists in gudang'
            ], 422);
        }

        $barang->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    public function stockSummary(Request $request)
    {
        $user = Auth::user();
        
        // ✅ Only admin and manager can view stock summary
        if (!$user->hasRole(['admin', 'manager'])) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        $query = Barang::with(['jenisBarang'])
            ->leftJoin('gudang', 'barang.id_barang', '=', 'gudang.id_barang');

        // ✅ Apply role-based filtering for stock data
        if ($user->hasRole('manager')) {
            // Manager: Only their branch stock
            $query->leftJoin('users', 'gudang.unique_id', '=', 'users.unique_id')
                  ->where(function($q) use ($user) {
                      $q->whereNull('users.branch_name')
                        ->orWhere('users.branch_name', $user->branch_name);
                  });
        }

        $stockData = $query->select([
                'barang.*',
                \DB::raw('COALESCE(SUM(gudang.jumlah_barang), 0) as total_stock')
            ])
            ->groupBy('barang.id_barang')
            ->get()
            ->map(function($item) {
                $item->stock_status = $this->getStockStatus($item->total_stock, $item->batas_minimum);
                return $item;
            });

        return response()->json(['status' => true, 'data' => $stockData]);
    }

    private function getStockStatus($currentStock, $minStock)
    {
        if ($currentStock == 0) return 'out_of_stock';
        if ($currentStock <= $minStock) return 'low_stock';
        return 'normal';
    }
}
