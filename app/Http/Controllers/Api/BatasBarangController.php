<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BatasBarangResource;
use App\Http\Requests\StoreBatasBarangRequest;
use App\Http\Requests\UpdateBatasBarangRequest;
use App\Http\Requests\CheckAllocationRequest;
use App\Models\BatasBarang;
use App\Services\BatasBarangService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BatasBarangController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected BatasBarangService $batasBarangService)
    {
        $this->authorizeResource(BatasBarang::class, 'batas_barang');
    }

    public function index(Request $request): JsonResponse
    {
        $query = BatasBarang::with(['barang.jenisBarang']);

        $query->when($request->filled('search'), function ($q) use ($request) {
            $q->whereHas('barang', fn($sq) => $sq->where('nama_barang', 'like', "%{$request->input('search')}%"));
        });

        $batasBarang = $query->paginate(20);

        // This feature is now better handled by the dedicated checkAllocation endpoint.
        // The index should remain a simple, fast list.

        return BatasBarangResource::collection($batasBarang)->response();
    }

    public function store(StoreBatasBarangRequest $request): JsonResponse
    {
        $batas = $this->batasBarangService->create($request->validated());
        return BatasBarangResource::make($batas)
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(BatasBarang $batas_barang): JsonResponse
    {
        return BatasBarangResource::make($batas_barang)->response();
    }

    public function update(UpdateBatasBarangRequest $request, BatasBarang $batas_barang): JsonResponse
    {
        $batas = $this->batasBarangService->update($batas_barang, $request->validated());
        return BatasBarangResource::make($batas)->response();
    }

    public function destroy(BatasBarang $batas_barang): JsonResponse
    {
        $batas_barang->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    // --- Custom Actions ---
    public function checkAllocation(CheckAllocationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $results = $this->batasBarangService->checkAllocation($validated['unique_id'], $validated['items']);
        
        return response()->json([
            'status' => true,
            'data'   => $results,
        ]);
    }
}