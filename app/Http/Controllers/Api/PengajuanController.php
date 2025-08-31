<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PengajuanResource;
use App\Models\Pengajuan;
use App\Services\PengajuanValidationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PengajuanController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Pengajuan::class);
        
        $user = Auth::user();
        $query = Pengajuan::with(['user', 'details.barang']);
        
        // ✅ CRITICAL: Apply role-based scope filtering
        if ($user->hasRole('admin')) {
            // ✅ Admin: Can see all pengajuan (global access)
            // No additional filtering needed - admin sees everything
            
        } elseif ($user->hasRole('manager')) {
            // ✅ Manager: Only their branch
            $query->whereHas('user', fn($q) => 
                $q->where('branch_name', $user->branch_name)
            );
            
        } elseif ($user->hasRole('user')) {
            // ✅ User: Only their own pengajuan
            $query->where('unique_id', $user->unique_id);
            
        } else {
            // ✅ No role = no access
            return response()->json([
                'status' => false, 
                'message' => 'Access denied - no valid role'
            ], 403);
        }
        
        // ✅ Apply additional filters (after scope filtering)
        if ($request->filled('status')) {
            $query->where('status_pengajuan', $request->status);
        }
        
        // ✅ Branch filter (only for admin)
        if ($request->filled('branch') && $user->hasRole('admin')) {
            $query->whereHas('user', fn($q) => 
                $q->where('branch_name', $request->branch)
            );
        }
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('id_pengajuan', 'like', "%{$request->search}%")
                  ->orWhereHas('user', fn($sq) => 
                      $sq->where('username', 'like', "%{$request->search}%")
                  );
            });
        }
        
        $pengajuan = $query->paginate(20);
        return PengajuanResource::collection($pengajuan);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Pengajuan::class);
        
        $user = Auth::user();
        
        // ✅ SECURITY: Users can only create for themselves
        $data = $request->validate([
            'id_pengajuan'    => 'required|string|unique:tb_pengajuan,id_pengajuan',
            'unique_id'       => 'required|string|exists:tb_users,unique_id',
            'status_pengajuan'=> 'sometimes|in:Menunggu Persetujuan,Disetujui,Ditolak',
            'tipe_pengajuan'  => 'sometimes|in:manual,biasa,mandiri',
            'keterangan'      => 'nullable|string|max:500',
            'bukti_file'      => 'nullable|file|mimes:jpeg,png,jpg|max:5120',
            'items'           => 'sometimes|array|min:1',
            'items.*.id_barang' => 'required_with:items|string|exists:tb_barang,id_barang',
            'items.*.jumlah'    => 'required_with:items|integer|min:1',
        ]);
        
        // ✅ SECURITY: Prevent users from creating pengajuan for others
        if ($user->hasRole('user') && $data['unique_id'] !== $user->unique_id) {
            return response()->json([
                'status' => false,
                'message' => 'You can only create pengajuan for yourself'
            ], 403);
        }
        
        // ✅ SECURITY: Only admin can create for other users
        if (!$user->hasRole('admin') && $data['unique_id'] !== $user->unique_id) {
            return response()->json([
                'status' => false,
                'message' => 'Only admin can create pengajuan for other users'
            ], 403);
        }

        // ✅ Business validation for limits
        if (isset($data['items'])) {
            $validationErrors = PengajuanValidationService::validatePengajuanLimits($data['unique_id'], $data['items']);
            if (!empty($validationErrors)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validationErrors
                ], 422);
            }
        }

        // ✅ Handle file upload for mandiri type
        $buktiFilePath = null;
        if ($request->hasFile('bukti_file') && ($data['tipe_pengajuan'] ?? 'biasa') === 'mandiri') {
            $file = $request->file('bukti_file');
            $buktiFilePath = $file->store('bukti-pengajuan', 'public');
        }

        $data['tipe_pengajuan'] = $data['tipe_pengajuan'] ?? 'biasa';
        $data['status_pengajuan'] = $data['status_pengajuan'] ?? 'Menunggu Persetujuan';
        $data['bukti_file'] = $buktiFilePath;

        $pengajuan = Pengajuan::create($data);
        
        return (new PengajuanResource($pengajuan))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show($id_pengajuan)
    {
        $user = Auth::user();
        $query = Pengajuan::with(['user', 'details.barang', 'approver', 'rejector'])
            ->where('id_pengajuan', $id_pengajuan);
            
        // ✅ CRITICAL: Apply scope filtering before finding
        if ($user->hasRole('admin')) {
            // Admin can see any pengajuan
        } elseif ($user->hasRole('manager')) {
            // Manager: Only their branch
            $query->whereHas('user', fn($q) => 
                $q->where('branch_name', $user->branch_name)
            );
        } elseif ($user->hasRole('user')) {
            // User: Only their own
            $query->where('unique_id', $user->unique_id);
        } else {
            return response()->json([
                'status' => false, 
                'message' => 'Access denied'
            ], 403);
        }
        
        $pengajuan = $query->firstOrFail();
        $this->authorize('view', $pengajuan);
        
        return new PengajuanResource($pengajuan);
    }

    public function update(Request $request, $id_pengajuan)
    {
        $user = Auth::user();
        $query = Pengajuan::where('id_pengajuan', $id_pengajuan);
        
        // ✅ CRITICAL: Apply scope filtering before finding
        if ($user->hasRole('admin')) {
            // Admin can update any pengajuan
        } elseif ($user->hasRole('manager')) {
            // Manager: Only their branch (but typically can't update)
            $query->whereHas('user', fn($q) => 
                $q->where('branch_name', $user->branch_name)
            );
        } elseif ($user->hasRole('user')) {
            // User: Only their own (but typically can't update)
            $query->where('unique_id', $user->unique_id);
        } else {
            return response()->json([
                'status' => false, 
                'message' => 'Access denied'
            ], 403);
        }
        
        $pengajuan = $query->firstOrFail();
        $this->authorize('update', $pengajuan);

        $data = $request->validate([
            'status_pengajuan'  => 'sometimes|required|in:Menunggu Persetujuan,Disetujui,Ditolak',
            'rejection_reason'  => 'required_if:status_pengajuan,Ditolak|string|max:500',
            'approval_notes'    => 'nullable|string|max:500',
            'keterangan'        => 'nullable|string|max:500',
        ]);

        // ✅ Handle status change with audit trail
        if (isset($data['status_pengajuan']) && $data['status_pengajuan'] !== $pengajuan->status_pengajuan) {
            
            // ✅ Validate stock limits before approval
            if ($data['status_pengajuan'] === 'Disetujui') {
                $stockErrors = $pengajuan->validateStockLimits();
                if (!empty($stockErrors)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Stock validation failed',
                        'errors' => $stockErrors
                    ], 422);
                }
            }

            // ✅ Use the new audit method
            $pengajuan->updateStatus($data['status_pengajuan'], [
                'approval_notes' => $data['approval_notes'] ?? null,
                'rejection_reason' => $data['rejection_reason'] ?? null,
            ]);

            // ✅ Handle stock movement for approved biasa/manual requests
            if ($data['status_pengajuan'] === 'Disetujui' && in_array($pengajuan->tipe_pengajuan, ['biasa', 'manual'])) {
                DB::transaction(function () use ($pengajuan) {
                    foreach ($pengajuan->details as $detail) {
                        // Add to user gudang
                        $userGudang = \App\Models\Gudang::firstOrCreate(
                            ['unique_id' => $pengajuan->unique_id, 'id_barang' => $detail->id_barang],
                            ['jumlah_barang' => 0]
                        );
                        $userGudang->jumlah_barang += $detail->jumlah;
                        $userGudang->save();
                    }
                });
            }

            return new PengajuanResource($pengajuan->fresh());
        }

        // ✅ Update other fields
        $pengajuan->update(array_intersect_key($data, array_flip(['keterangan'])));
        
        return new PengajuanResource($pengajuan);
    }

    public function destroy($id_pengajuan)
    {
        $user = Auth::user();
        $query = Pengajuan::where('id_pengajuan', $id_pengajuan);
        
        // ✅ CRITICAL: Apply scope filtering before finding
        if ($user->hasRole('admin')) {
            // Admin can delete any pengajuan
        } elseif ($user->hasRole('manager')) {
            // Manager: Only their branch (but typically can't delete)
            $query->whereHas('user', fn($q) => 
                $q->where('branch_name', $user->branch_name)
            );
        } elseif ($user->hasRole('user')) {
            // User: Only their own
            $query->where('unique_id', $user->unique_id);
        } else {
            return response()->json([
                'status' => false, 
                'message' => 'Access denied'
            ], 403);
        }
        
        $pengajuan = $query->firstOrFail();
        $this->authorize('delete', $pengajuan);
        
        // ✅ Only allow deletion of pending or rejected requests
        if (!$pengajuan->canBeDeleted()) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete approved or completed pengajuan'
            ], 422);
        }

        // ✅ Delete associated file if exists
        if ($pengajuan->bukti_file && Storage::disk('public')->exists($pengajuan->bukti_file)) {
            Storage::disk('public')->delete($pengajuan->bukti_file);
        }
        
        $pengajuan->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
