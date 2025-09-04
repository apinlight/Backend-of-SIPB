<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DetailPengajuanResource;
use App\Http\Requests\StoreDetailPengajuanRequest;
use App\Models\DetailPengajuan;
use App\Models\Pengajuan;
use App\Services\PengajuanService; // Use the main service
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DetailPengajuanController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected PengajuanService $pengajuanService)
    {
        // ❌ REMOVE THIS to prevent the 500 error
        // $this->authorizeResource(DetailPengajuan::class, 'detail_pengajuan');
    }
    
    public function store(StoreDetailPengajuanRequest $request): JsonResponse
    {
        // Authorization is correctly handled by StoreDetailPengajuanRequest
        $pengajuan = Pengajuan::findOrFail($request->validated()['id_pengajuan']);
        
        $detail = $this->pengajuanService->addItem($pengajuan, $request->validated());

        return (new DetailPengajuanResource($detail))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function update(Request $request, DetailPengajuan $detail_pengajuan): JsonResponse
    {
        // ✅ ADD manual authorization for updating the item
        $this->authorize('update', $detail_pengajuan);

        $validatedData = $request->validate([
            'jumlah'     => 'sometimes|required|integer|min:1',
            'keterangan' => 'nullable|string|max:500',
        ]);
        
        $updatedDetail = $this->pengajuanService->updateItem($detail_pengajuan, $validatedData);

        return (new DetailPengajuanResource($updatedDetail))->response();
    }

    public function destroy(DetailPengajuan $detail_pengajuan): JsonResponse
    {
        // ✅ ADD manual authorization for deleting the item
        $this->authorize('delete', $detail_pengajuan);

        $this->pengajuanService->removeItem($detail_pengajuan);
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}