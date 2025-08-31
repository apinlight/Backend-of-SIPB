<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GudangResource;
use App\Http\Requests\StoreGudangRequest;
use App\Http\Requests\AdjustStockRequest;
use App\Models\Gudang;
use App\Models\User;
use App\Services\GudangService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class GudangController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected GudangService $gudangService)
    {
        // Since Gudang is a pivot, we authorize manually instead of using authorizeResource
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Gudang::class);
        
        $query = Gudang::with(['user', 'barang'])->forUser($request->user());

        // Admin filters
        if ($request->user()->hasRole('admin')) {
            $query->when($request->filled('branch'), function ($q) use ($request) {
                $q->whereHas('user', fn($sq) => $sq->where('branch_name', $request->input('branch')));
            });
            $query->when($request->filled('user_id'), function ($q) use ($request) {
                $q->where('unique_id', $request->input('user_id'));
            });
        }

        $query->when($request->filled('search'), function ($q) use ($request) {
            $q->whereHas('barang', fn($sq) => $sq->where('nama_barang', 'like', "%{$request->input('search')}%"));
        });

        $gudang = $query->paginate(20);
        return GudangResource::collection($gudang)->response();
    }

    public function store(StoreGudangRequest $request): JsonResponse
    {
        $gudang = $this->gudangService->createOrUpdate($request->validated());
        return GudangResource::make($gudang)
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show($unique_id, $id_barang): JsonResponse
    {
        $gudang = Gudang::where('unique_id', $unique_id)
            ->where('id_barang', $id_barang)
            ->firstOrFail();

        $this->authorize('view', $gudang);
        return GudangResource::make($gudang)->response();
    }

    public function update(Request $request, $unique_id, $id_barang): JsonResponse
    {
        $gudang = Gudang::where('unique_id', $unique_id)
            ->where('id_barang', $id_barang)
            ->firstOrFail();
            
        $this->authorize('update', $gudang);

        $validatedData = $request->validate([
            'jumlah_barang' => 'sometimes|required|integer|min:0',
            'keterangan'    => 'nullable|string|max:500',
        ]);
        
        $gudang->update($validatedData);
        return GudangResource::make($gudang)->response();
    }

    public function destroy($unique_id, $id_barang): JsonResponse
    {
        $gudang = Gudang::where('unique_id', $unique_id)
            ->where('id_barang', $id_barang)
            ->firstOrFail();

        $this->authorize('delete', $gudang);
        
        $gudang->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    public function adjustStock(AdjustStockRequest $request, $unique_id, $id_barang): JsonResponse
    {
        $gudang = Gudang::where('unique_id', $unique_id)
            ->where('id_barang', $id_barang)
            ->firstOrFail();

        $updatedGudang = $this->gudangService->adjustStock(
            $gudang,
            $request->user(),
            $request->validated()
        );

        return GudangResource::make($updatedGudang)->response();
    }
}