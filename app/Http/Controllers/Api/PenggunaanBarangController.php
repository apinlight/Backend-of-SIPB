<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PenggunaanBarangResource;
use App\Http\Requests\StorePenggunaanBarangRequest;
use App\Models\PenggunaanBarang;
use App\Services\PenggunaanBarangService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PenggunaanBarangController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected PenggunaanBarangService $penggunaanBarangService)
    {
        $this->authorizeResource(PenggunaanBarang::class, 'penggunaan_barang');
    }

    public function index(Request $request): JsonResponse
    {
        $query = PenggunaanBarang::with(['user', 'barang.jenisBarang', 'approver'])
            ->forUser($request->user());

        $query->when($request->filled('status'), fn($q) => $q->where('status', 'like', "%{$request->status}%"));
        $query->when($request->filled('id_barang'), fn($q) => $q->where('id_barang', $request->id_barang));
        $query->when($request->filled('search'), function($q) use ($request) {
            $q->whereHas('barang', fn($sq) => $sq->where('nama_barang', 'like', "%{$request->search}%"))
              ->orWhere('keperluan', 'like', "%{$request->search}%");
        });

        $penggunaan = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return PenggunaanBarangResource::collection($penggunaan)->response();
    }

    public function store(StorePenggunaanBarangRequest $request): JsonResponse
    {
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
        return (new PenggunaanBarangResource($penggunaan_barang->load(['user', 'barang.jenisBarang', 'approver'])))
            ->response();
    }

    public function update(Request $request, PenggunaanBarang $penggunaan_barang): JsonResponse
    {
        $penggunaan_barang->update($request->all());
        return (new PenggunaanBarangResource($penggunaan_barang))->response();
    }

    public function destroy(PenggunaanBarang $penggunaan_barang): JsonResponse
    {
        $penggunaan_barang->delete();
        return response()->json(null, 204);
    }

    public function approve(Request $request, PenggunaanBarang $penggunaan_barang): JsonResponse
    {
        $this->authorize('approve', $penggunaan_barang);

        // ✅ FIX: Use the injected Request object to get the user
        $updatedPenggunaan = $this->penggunaanBarangService->approve($penggunaan_barang, $request->user());

        return (new PenggunaanBarangResource($updatedPenggunaan))->response();
    }

    public function reject(Request $request, PenggunaanBarang $penggunaan_barang): JsonResponse
    {
        $this->authorize('approve', $penggunaan_barang);

        // ✅ FIX: Use the injected Request object to get the user
        $updatedPenggunaan = $this->penggunaanBarangService->reject(
            $penggunaan_barang,
            $request->user(),
            $request->input('rejection_reason')
        );

        return (new PenggunaanBarangResource($updatedPenggunaan))->response();
    }
}