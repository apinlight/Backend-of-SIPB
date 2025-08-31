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
        // The parameter name must match the route parameter, e.g., 'barang'
        $this->authorizeResource(Barang::class, 'barang');
    }

    public function index(Request $request): JsonResponse
    {
        $query = Barang::with(['jenisBarang']);

        $query->when($request->filled('search'), fn($q) => $q->where('nama_barang', 'like', "%{$request->search}%"));
        $query->when($request->filled('jenis'), fn($q) => $q->where('id_jenis_barang', $request->jenis));

        $barang = $query->paginate(20);
        return BarangResource::collection($barang)->response();
    }

    public function store(StoreBarangRequest $request): JsonResponse
    {
        $barang = $this->barangService->create($request->validated());
        return (new BarangResource($barang->load('jenisBarang')))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Barang $barang): JsonResponse
    {
        return (new BarangResource($barang->load('jenisBarang')))->response();
    }

    public function update(UpdateBarangRequest $request, Barang $barang): JsonResponse
    {
        $updatedBarang = $this->barangService->update($barang, $request->validated());
        return (new BarangResource($updatedBarang))->response();
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