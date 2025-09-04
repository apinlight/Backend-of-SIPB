<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BarangResource;
use App\Http\Requests\StoreBarangRequest;
use App\Http\Requests\UpdateBarangRequest;
use App\Models\Barang;
use App\Services\BarangService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BarangController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected BarangService $barangService)
    {
        // ❌ The problematic authorizeResource() line is removed.
    }

    public function index(Request $request): JsonResponse
    {
        // ✅ Manual authorization is added.
        $this->authorize('viewAny', Barang::class);

        $query = Barang::with(['jenisBarang']);

        // All original filters are preserved.
        $query->when($request->filled('search'), function ($q) use ($request) {
            return $q->where('nama_barang', 'like', '%' . $request->input('search') . '%');
        });
        $query->when($request->filled('jenis'), function ($q) use ($request) {
            return $q->where('id_jenis_barang', $request->input('jenis'));
        });
        $query->when($request->filled('harga_min'), function ($q) use ($request) {
            return $q->where('harga_barang', '>=', $request->input('harga_min'));
        });
        $query->when($request->filled('harga_max'), function ($q) use ($request) {
            return $q->where('harga_barang', '<=', $request->input('harga_max'));
        });

        $barang = $query->paginate(20);

        return BarangResource::collection($barang)->response();
    }

    public function store(StoreBarangRequest $request): JsonResponse
    {
        // Authorization is handled by the StoreBarangRequest Form Request.
        $barang = $this->barangService->create($request->validated());
        
        return BarangResource::make($barang->load('jenisBarang'))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Barang $barang): JsonResponse
    {
        // ✅ Manual authorization is added.
        $this->authorize('view', $barang);

        return BarangResource::make($barang->load('jenisBarang'))->response();
    }

    public function update(UpdateBarangRequest $request, Barang $barang): JsonResponse
    {
        // Authorization is handled by the UpdateBarangRequest Form Request.
        $updatedBarang = $this->barangService->update($barang, $request->validated());
        return BarangResource::make($updatedBarang)->response();
    }

    public function destroy(Barang $barang): JsonResponse
    {
        // ✅ Manual authorization is added.
        $this->authorize('delete', $barang);

        try {
            $this->barangService->delete($barang);
            return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }
}