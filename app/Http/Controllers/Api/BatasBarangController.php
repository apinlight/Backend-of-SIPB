<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BatasBarangResource;
use App\Models\BatasBarang;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BatasBarangController extends Controller
{
    use AuthorizesRequests;

    // GET /api/batas-barang
    public function index(Request $request)
    {
        $this->authorize('viewAny', BatasBarang::class);
        
        $user = Auth::user();
        $query = BatasBarang::with(['barang.jenis_barang']);
        
        // Apply filters...
        if ($request->filled('search')) {
            $query->whereHas('barang', fn($q) => 
                $q->where('nama_barang', 'like', "%{$request->search}%")
            );
        }
        
        $batasBarang = $query->paginate(20);
        
        // ✅ ADD: Enhance with current stock for specific user
        if ($request->filled('with_stock_for')) {
            $targetUserId = $request->input('with_stock_for');
            
            $batasBarang->getCollection()->transform(function ($item) use ($targetUserId) {
                $currentStock = Gudang::where('unique_id', $targetUserId)
                    ->where('id_barang', $item->id_barang)
                    ->value('jumlah_barang') ?? 0;
                    
                $item->current_stock = $currentStock;
                $item->available_allocation = max(0, $item->batas_barang - $currentStock);
                $item->allocation_percentage = $item->batas_barang > 0 
                    ? round(($currentStock / $item->batas_barang) * 100, 1) 
                    : 0;
                    
                return $item;
            });
        }
        
        return BatasBarangResource::collection($batasBarang);
    }

    // POST /api/batas-barang
    public function store(Request $request)
    {
        $this->authorize('create', BatasBarang::class);

        $data = $request->validate([
            'id_barang'   => 'required|string',
            'batas_barang'=> 'required|integer|min:0',
        ]);

        $batas = BatasBarang::create($data);
        return (new BatasBarangResource($batas))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    // GET /api/batas-barang/{id_barang}
    public function show($id_barang)
    {
        $batas = BatasBarang::findOrFail($id_barang);
        $this->authorize('view', $batas);

        return (new BatasBarangResource($batas))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }

    // PUT/PATCH /api/batas-barang/{id_barang}
    public function update(Request $request, $id_barang)
    {
        $batas = BatasBarang::findOrFail($id_barang);
        $this->authorize('update', $batas);

        $data = $request->validate([
            'batas_barang'=> 'sometimes|required|integer|min:0',
        ]);
        $batas->update($data);
        return (new BatasBarangResource($batas))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }

    // DELETE /api/batas-barang/{id_barang}
    public function destroy($id_barang)
    {
        $batas = BatasBarang::findOrFail($id_barang);
        $this->authorize('delete', $batas);

        $batas->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    public function checkAllocation(Request $request)
    {
        $this->authorize('viewAny', BatasBarang::class);
        
        $data = $request->validate([
            'unique_id' => 'required|string|exists:tb_users,unique_id',
            'items' => 'required|array',
            'items.*.id_barang' => 'required|string|exists:tb_barang,id_barang',
            'items.*.jumlah' => 'required|integer|min:1'
        ]);
        
        $results = [];
        
        foreach ($data['items'] as $item) {
            $currentStock = Gudang::where('unique_id', $data['unique_id'])
                ->where('id_barang', $item['id_barang'])
                ->value('jumlah_barang') ?? 0;
                
            $batasBarang = BatasBarang::where('id_barang', $item['id_barang'])
                ->value('batas_barang') ?? PHP_INT_MAX;
                
            $newTotal = $currentStock + $item['jumlah'];
            $available = max(0, $batasBarang - $currentStock);
            
            $results[] = [
                'id_barang' => $item['id_barang'],
                'current_stock' => $currentStock,
                'batas_barang' => $batasBarang,
                'requested' => $item['jumlah'],
                'new_total' => $newTotal,
                'available' => $available,
                'is_valid' => $newTotal <= $batasBarang,
                'message' => $newTotal > $batasBarang 
                    ? "Melebihi batas ({$newTotal} > {$batasBarang})" 
                    : 'Valid'
            ];
        }
        
        return response()->json([
            'status' => true,
            'data' => $results
        ]);
    }
}
