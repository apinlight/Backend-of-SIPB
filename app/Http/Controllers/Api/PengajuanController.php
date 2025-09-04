<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PengajuanResource;
use App\Http\Requests\StorePengajuanRequest;
use App\Models\Pengajuan;
use App\Services\PengajuanService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PengajuanController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected PengajuanService $pengajuanService)
    {
        // ❌ REMOVE THIS to prevent the 500 error
        // $this->authorizeResource(Pengajuan::class, 'pengajuan');
    }

    public function index(Request $request): JsonResponse
    {
        // ✅ ADD manual authorization
        $this->authorize('viewAny', Pengajuan::class);

        $query = Pengajuan::with(['user', 'details.barang'])
            ->forUser($request->user()); // Assumes forUser scope exists

        $query->when($request->filled('status'), fn($q) => $q->where('status_pengajuan', $request->status));
        $query->when($request->user()->hasRole('admin') && $request->filled('branch'), function ($q) use ($request) {
            $q->whereHas('user', fn($sq) => $sq->where('branch_name', $request->branch));
        });

        $pengajuan = $query->paginate(20);
        return PengajuanResource::collection($pengajuan)->response();
    }

    public function store(StorePengajuanRequest $request): JsonResponse
    {
        // Authorization is correctly handled by StorePengajuanRequest
        try {
            $pengajuan = $this->pengajuanService->create(
                $request->validated(),
                $request->hasFile('bukti_file') ? $request->file('bukti_file') : null
            );
            return (new PengajuanResource($pengajuan))->response()->setStatusCode(HttpResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => json_decode($e->getMessage())], 422);
        }
    }

    public function show(Pengajuan $pengajuan): JsonResponse
    {
        // ✅ ADD manual authorization
        $this->authorize('view', $pengajuan);
        
        return (new PengajuanResource($pengajuan->load(['user', 'details.barang', 'approver', 'rejector'])))->response();
    }

    public function update(Request $request, Pengajuan $pengajuan): JsonResponse
    {
        // This method already had the correct authorization check. Excellent.
        $this->authorize('update', $pengajuan);

        $data = $request->validate([
            'status_pengajuan' => 'sometimes|required|in:Menunggu Persetujuan,Disetujui,Ditolak',
            'rejection_reason' => 'required_if:status_pengajuan,Ditolak|string|max:500',
            'approval_notes'   => 'nullable|string|max:500',
        ]);

        try {
            $updatedPengajuan = $this->pengajuanService->updateStatus($pengajuan, $request->user(), $data);
            return (new PengajuanResource($updatedPengajuan))->response();
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Stock validation failed', 'errors' => json_decode($e->getMessage())], 422);
        }
    }

    public function destroy(Pengajuan $pengajuan): JsonResponse
    {
        // ✅ ADD manual authorization
        $this->authorize('delete', $pengajuan);
        
        try {
            $this->pengajuanService->delete($pengajuan);
            return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }
}