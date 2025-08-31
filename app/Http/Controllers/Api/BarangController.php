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
        $this->authorizeResource(Barang::class, 'barang');
    }

    public function index(Request $request): JsonResponse
    {
        $query = Barang::with(['jenisBarang']);

        // ✅ FIX: Use the more explicit `function() use ($request)` syntax to make the scope
        // clear to static analysis tools and fix the "Undefined property" error.
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

        // NOTE: The `::collection()` method is the correct Laravel convention.
        return BarangResource::collection($barang)->response();
    }

    public function store(StoreBarangRequest $request): JsonResponse
    {
        $barang = $this->barangService->create($request->validated());
        
        // NOTE: The `::make()` method is the correct Laravel convention. Any IDE warning
        // on this line is a known false positive and can be safely ignored.
        return BarangResource::make($barang->load('jenisBarang'))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Barang $barang): JsonResponse
    {
        // NOTE: The `::make()` method is the correct Laravel convention.
        return BarangResource::make($barang->load('jenisBarang'))->response();
    }

    public function update(UpdateBarangRequest $request, Barang $barang): JsonResponse
    {
        $updatedBarang = $this->barangService->update($barang, $request->validated());
        // NOTE: The `::make()` method is the correct Laravel convention.
        return BarangResource::make($updatedBarang)->response();
    }

    public function destroy(Barang $barang): JsonResponse
    {
        try {
            $this->barangService->delete($barang);
            return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }
}