<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckAllocationRequest;
use App\Http\Requests\StoreBatasBarangRequest;
use App\Http\Requests\UpdateBatasBarangRequest;
use App\Http\Resources\BatasBarangResource;
use App\Models\BatasBarang;
use App\Services\BatasBarangService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BatasBarangController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected BatasBarangService $batasBarangService)
    {
        // ❌ REMOVE THIS to prevent the 500 error
        // $this->authorizeResource(BatasBarang::class, 'batas_barang');
    }

    public function index(Request $request): JsonResponse
    {
        // ✅ ADD manual authorization for viewing the list
        $this->authorize('viewAny', BatasBarang::class);

        $query = BatasBarang::with(['barang.jenisBarang']);

        $query->when($request->filled('search'), function ($q) use ($request) {
            $q->whereHas('barang', fn ($sq) => $sq->where('nama_barang', 'like', "%{$request->input('search')}%"));
        });

        $batasBarang = $query->paginate(20);

        return BatasBarangResource::collection($batasBarang)->response();
    }

    public function store(StoreBatasBarangRequest $request): JsonResponse
    {
        // Authorization is correctly handled by StoreBatasBarangRequest
        $batas = $this->batasBarangService->create($request->validated());

        return BatasBarangResource::make($batas)
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(BatasBarang $batas_barang): JsonResponse
    {
        // ✅ ADD manual authorization for viewing a single item
        $this->authorize('view', $batas_barang);

        return BatasBarangResource::make($batas_barang)->response();
    }

    public function update(UpdateBatasBarangRequest $request, BatasBarang $batas_barang): JsonResponse
    {
        // Authorization is correctly handled by UpdateBatasBarangRequest
        $batas = $this->batasBarangService->update($batas_barang, $request->validated());

        return BatasBarangResource::make($batas)->response();
    }

    public function destroy(BatasBarang $batas_barang): JsonResponse
    {
        // ✅ ADD manual authorization for deleting an item
        $this->authorize('delete', $batas_barang);

        $batas_barang->delete();

        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    // --- Custom Actions ---
    public function checkAllocation(CheckAllocationRequest $request): JsonResponse
    {
        // Authorization is correctly handled by CheckAllocationRequest
        $validated = $request->validated();
        $results = $this->batasBarangService->checkAllocation($validated['unique_id'], $validated['items']);

        return response()->json([
            'status' => true,
            'data' => $results,
        ]);
    }
}
