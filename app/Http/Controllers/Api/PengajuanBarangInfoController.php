<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PengajuanBarangInfoResource;
use App\Services\PengajuanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengajuanBarangInfoController extends Controller
{
    public function __construct(protected PengajuanService $pengajuanService) {}

    public function getBarangInfo(Request $request): JsonResponse
    {
        $filters = $request->validate(['search' => 'nullable|string|max:255']);

        $data = $this->pengajuanService->getInfoForForm($request->user(), $filters);

        return response()->json([
            'status' => true,
            'data' => PengajuanBarangInfoResource::make((object) $data),
        ]);
    }

    public function getBarangPengajuanHistory(Request $request, $idBarang): JsonResponse
    {
        $validated = $request->validate(['months' => 'sometimes|integer|min:1|max:12']);
        $months = $validated['months'] ?? 6;

        $data = $this->pengajuanService->getItemHistory($request->user(), $idBarang, $months);

        return response()->json(['status' => true, 'data' => $data]);
    }
}
