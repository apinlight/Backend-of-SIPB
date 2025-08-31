<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DetailPengajuanResource;
use App\Models\DetailPengajuan;
use App\Models\Pengajuan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DetailPengajuanController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', DetailPengajuan::class);
        
        $user = Auth::user();
        $query = DetailPengajuan::with(['pengajuan.user', 'barang']);

        // ✅ CRITICAL: Apply role-based scope filtering
        if ($user->hasRole('admin')) {
            // Admin can see all detail pengajuan
            // Optional pengajuan filter
            if ($request->filled('id_pengajuan')) {
                $query->where('id_pengajuan', $request->id_pengajuan);
            }
            
        } elseif ($user->hasRole('manager')) {
            // Manager: Only their branch's pengajuan details
            $query->whereHas('pengajuan.user', fn($q) => 
                $q->where('branch_name', $user->branch_name)
            );
            
        } elseif ($user->hasRole('user')) {
            // User: Only their own pengajuan details
            $query->whereHas('pengajuan', fn($q) => 
                $q->where('unique_id', $user->unique_id)
            );
            
        } else {
            return response()->json([
                'status' => false, 
                'message' => 'Access denied - no valid role'
            ], 403);
        }

        // ✅ Additional filters
        if ($request->filled('id_pengajuan')) {
            $query->where('id_pengajuan', $request->id_pengajuan);
        }

        if ($request->filled('id_barang')) {
            $query->where('id_barang', $request->id_barang);
        }

        $details = $query->paginate(20);
        return DetailPengajuanResource::collection($details);
    }

    public function store(Request $request)
    {
        $this->authorize('create', DetailPengajuan::class);
        
        $user = Auth::user();
        
        $data = $request->validate([
            'id_pengajuan' => 'required|string|exists:tb_pengajuan,id_pengajuan',
            'id_barang'    => 'required|string|exists:tb_barang,id_barang',
            'jumlah'       => 'required|integer|min:1',
            'keterangan'   => 'nullable|string|max:500',
        ]);

        // ✅ SECURITY: Check if user can modify this pengajuan
        $pengajuan = Pengajuan::where('id_pengajuan', $data['id_pengajuan'])->firstOrFail();
        
        if ($user->hasRole('user') && $pengajuan->unique_id !== $user->unique_id) {
            return response()->json([
                'status' => false,
                'message' => 'You can only add details to your own pengajuan'
            ], 403);
        }

        if ($user->hasRole('manager')) {
            $pengajuanUser = $pengajuan->user;
            if (!$pengajuanUser || $pengajuanUser->branch_name !== $user->branch_name) {
                return response()->json([
                    'status' => false,
                    'message' => 'Manager can only modify pengajuan from their branch'
                ], 403);
            }
        }

        // ✅ Check if pengajuan can be modified
        if (!in_array($pengajuan->status_pengajuan, ['Menunggu Persetujuan', 'Draft'])) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot modify approved or rejected pengajuan'
            ], 422);
        }

        // ✅ Check if detail already exists
        $existingDetail = DetailPengajuan::where('id_pengajuan', $data['id_pengajuan'])
                                        ->where('id_barang', $data['id_barang'])
                                        ->first();

        if ($existingDetail) {
            // ✅ Update existing detail
            $existingDetail->jumlah += $data['jumlah'];
            $existingDetail->keterangan = $data['keterangan'] ?? $existingDetail->keterangan;
            $existingDetail->save();
            
            return new DetailPengajuanResource($existingDetail->load(['pengajuan.user', 'barang']));
        } else {
            // ✅ Create new detail
            $detail = DetailPengajuan::create($data);
            return (new DetailPengajuanResource($detail->load(['pengajuan.user', 'barang'])))
                ->response()
                ->setStatusCode(HttpResponse::HTTP_CREATED);
        }
    }

    public function show($id_pengajuan, $id_barang)
    {
        $user = Auth::user();
        $query = DetailPengajuan::with(['pengajuan.user', 'barang'])
                               ->where('id_pengajuan', $id_pengajuan)
                               ->where('id_barang', $id_barang);

        // ✅ CRITICAL: Apply scope filtering before finding
        if ($user->hasRole('admin')) {
            // Admin can see any detail
        } elseif ($user->hasRole('manager')) {
            // Manager: Only their branch
            $query->whereHas('pengajuan.user', fn($q) => 
                $q->where('branch_name', $user->branch_name)
            );
        } elseif ($user->hasRole('user')) {
            // User: Only their own
            $query->whereHas('pengajuan', fn($q) => 
                $q->where('unique_id', $user->unique_id)
            );
        } else {
            return response()->json([
                'status' => false, 
                'message' => 'Access denied'
            ], 403);
        }

        $detail = $query->firstOrFail();
        $this->authorize('view', $detail);
        
        return new DetailPengajuanResource($detail);
    }

    public function update(Request $request, $id_pengajuan, $id_barang)
    {
        $user = Auth::user();
        $query = DetailPengajuan::where('id_pengajuan', $id_pengajuan)
                               ->where('id_barang', $id_barang);

        // ✅ CRITICAL: Apply scope filtering before finding
        if ($user->hasRole('admin')) {
            // Admin can update any detail
        } elseif ($user->hasRole('manager')) {
            // Manager: Only their branch
            $query->whereHas('pengajuan.user', fn($q) => 
                $q->where('branch_name', $user->branch_name)
            );
        } elseif ($user->hasRole('user')) {
            // User: Only their own
            $query->whereHas('pengajuan', fn($q) => 
                $q->where('unique_id', $user->unique_id)
            );
        } else {
            return response()->json([
                'status' => false, 
                'message' => 'Access denied'
            ], 403);
        }

        $detail = $query->firstOrFail();
        $this->authorize('update', $detail);

        // ✅ Check if pengajuan can be modified
        if (!in_array($detail->pengajuan->status_pengajuan, ['Menunggu Persetujuan', 'Draft'])) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot modify approved or rejected pengajuan'
            ], 422);
        }

        $data = $request->validate([
            'jumlah'     => 'sometimes|required|integer|min:1',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $detail->update($data);
        return new DetailPengajuanResource($detail->fresh(['pengajuan.user', 'barang']));
    }

    public function destroy($id_pengajuan, $id_barang)
    {
        $user = Auth::user();
        $query = DetailPengajuan::where('id_pengajuan', $id_pengajuan)
                               ->where('id_barang', $id_barang);

        // ✅ CRITICAL: Apply scope filtering before finding
        if ($user->hasRole('admin')) {
            // Admin can delete any detail
        } elseif ($user->hasRole('manager')) {
            // Manager: Only their branch
            $query->whereHas('pengajuan.user', fn($q) => 
                $q->where('branch_name', $user->branch_name)
            );
        } elseif ($user->hasRole('user')) {
            // User: Only their own
            $query->whereHas('pengajuan', fn($q) => 
                $q->where('unique_id', $user->unique_id)
            );
        } else {
            return response()->json([
                'status' => false, 
                'message' => 'Access denied'
            ], 403);
        }

        $detail = $query->firstOrFail();
        $this->authorize('delete', $detail);

        // ✅ Check if pengajuan can be modified
        if (!in_array($detail->pengajuan->status_pengajuan, ['Menunggu Persetujuan', 'Draft'])) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot modify approved or rejected pengajuan'
            ], 422);
        }

        $detail->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    public function bulkCreate(Request $request)
    {
        $this->authorize('create', DetailPengajuan::class);
        
        $user = Auth::user();
        
        $data = $request->validate([
            'id_pengajuan' => 'required|string|exists:tb_pengajuan,id_pengajuan',
            'items'        => 'required|array|min:1',
            'items.*.id_barang' => 'required|string|exists:tb_barang,id_barang',
            'items.*.jumlah'    => 'required|integer|min:1',
            'items.*.keterangan' => 'nullable|string|max:500',
        ]);

        // ✅ SECURITY: Check if user can modify this pengajuan
        $pengajuan = Pengajuan::where('id_pengajuan', $data['id_pengajuan'])->firstOrFail();
        
        if ($user->hasRole('user') && $pengajuan->unique_id !== $user->unique_id) {
            return response()->json([
                'status' => false,
                'message' => 'You can only add details to your own pengajuan'
            ], 403);
        }

        if ($user->hasRole('manager')) {
            $pengajuanUser = $pengajuan->user;
            if (!$pengajuanUser || $pengajuanUser->branch_name !== $user->branch_name) {
                return response()->json([
                    'status' => false,
                    'message' => 'Manager can only modify pengajuan from their branch'
                ], 403);
            }
        }

        // ✅ Check if pengajuan can be modified
        if (!in_array($pengajuan->status_pengajuan, ['Menunggu Persetujuan', 'Draft'])) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot modify approved or rejected pengajuan'
            ], 422);
        }

        $createdDetails = [];
        
        foreach ($data['items'] as $item) {
            $detailData = [
                'id_pengajuan' => $data['id_pengajuan'],
                'id_barang'    => $item['id_barang'],
                'jumlah'       => $item['jumlah'],
                'keterangan'   => $item['keterangan'] ?? null,
            ];

            // ✅ Check if detail already exists
            $existingDetail = DetailPengajuan::where('id_pengajuan', $data['id_pengajuan'])
                                            ->where('id_barang', $item['id_barang'])
                                            ->first();

            if ($existingDetail) {
                // ✅ Update existing detail
                $existingDetail->jumlah += $item['jumlah'];
                $existingDetail->keterangan = $item['keterangan'] ?? $existingDetail->keterangan;
                $existingDetail->save();
                $createdDetails[] = $existingDetail->load(['pengajuan.user', 'barang']);
            } else {
                // ✅ Create new detail
                $detail = DetailPengajuan::create($detailData);
                $createdDetails[] = $detail->load(['pengajuan.user', 'barang']);
            }
        }

        return DetailPengajuanResource::collection(collect($createdDetails));
    }

    public function getByPengajuan($id_pengajuan)
    {
        $user = Auth::user();
        $query = DetailPengajuan::with(['pengajuan.user', 'barang'])
                               ->where('id_pengajuan', $id_pengajuan);

        // ✅ CRITICAL: Apply scope filtering before finding
        if ($user->hasRole('admin')) {
            // Admin can see any pengajuan details
        } elseif ($user->hasRole('manager')) {
            // Manager: Only their branch
            $query->whereHas('pengajuan.user', fn($q) => 
                $q->where('branch_name', $user->branch_name)
            );
        } elseif ($user->hasRole('user')) {
            // User: Only their own
            $query->whereHas('pengajuan', fn($q) => 
                $q->where('unique_id', $user->unique_id)
            );
        } else {
            return response()->json([
                'status' => false, 
                'message' => 'Access denied'
            ], 403);
        }

        $details = $query->get();
        
        if ($details->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No details found or access denied'
            ], 404);
        }

        return DetailPengajuanResource::collection($details);
    }
}

