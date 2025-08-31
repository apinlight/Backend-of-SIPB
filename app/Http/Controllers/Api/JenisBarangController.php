<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JenisBarangResource;
use App\Http\Requests\StoreJenisBarangRequest;
use App\Http\Requests\UpdateJenisBarangRequest;
use App\Models\JenisBarang;
use App\Services\JenisBarangService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class JenisBarangController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected JenisBarangService $jenisBarangService)
    {
        $this->authorizeResource(JenisBarang::class, 'jenis_barang');
    }

    public function index(Request $request): JsonResponse
    {
        $query = JenisBarang::query();

        $query->when($request->filled('search'), function ($q) use ($request) {
            return $q->where('nama_jenis_barang', 'like', '%' . $request->input('search') . '%');
        });
        $query->when($request->filled('status'), function ($q) use ($request) {
            return $q->where('is_active', $request->input('status') === 'active');
        });

        $jenisBarang = $query->paginate(20);
        return JenisBarangResource::collection($jenisBarang)->response();
    }

    public function store(StoreJenisBarangRequest $request): JsonResponse
    {
        $jenisBarang = $this->jenisBarangService->create($request->validated());
        return JenisBarangResource::make($jenisBarang)
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(JenisBarang $jenis_barang): JsonResponse
    {
        return JenisBarangResource::make($jenis_barang)->response();
    }

    public function update(UpdateJenisBarangRequest $request, JenisBarang $jenis_barang): JsonResponse
    {
        $updatedJenisBarang = $this->jenisBarangService->update($jenis_barang, $request->validated());
        return JenisBarangResource::make($updatedJenisBarang)->response();
    }

    public function destroy(JenisBarang $jenis_barang): JsonResponse
    {
        try {
            $this->jenisBarangService->delete($jenis_barang);
            return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }
    
    // --- Custom Actions ---

    public function toggleStatus(JenisBarang $jenis_barang): JsonResponse
    {
        $this->authorize('update', $jenis_barang);
        $updatedJenisBarang = $this->jenisBarangService->toggleStatus($jenis_barang);
        return JenisBarangResource::make($updatedJenisBarang)->response();
    }

    public function active(): JsonResponse
    {
        $this->authorize('viewAny', JenisBarang::class);
        $jenisBarang = JenisBarang::where('is_active', true)->get();
        return JenisBarangResource::collection($jenisBarang)->response();
    }
}