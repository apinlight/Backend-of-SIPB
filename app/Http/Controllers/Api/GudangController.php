<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GudangResource;
use App\Models\Gudang;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class GudangController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        // ✅ Add authorization check
        $this->authorize('viewAny', Gudang::class);
        
        $user = Auth::user();
        $query = Gudang::with(['user', 'barang']);
        
        // ✅ CRITICAL: Apply role-based scope filtering
        if ($user->hasRole('admin')) {
            // ✅ Admin: Can see all gudang data
            // Optional branch filter
            if ($request->filled('branch')) {
                $query->whereHas('user', fn($q) => 
                    $q->where('branch_name', $request->branch)
                );
            }
            
            // Optional user filter for admin
            if ($request->filled('user_id')) {
                $query->where('unique_id', $request->user_id);
            }
            
        } elseif ($user->hasRole('manager')) {
            // ✅ Manager: Only their branch's gudang
            $query->whereHas('user', fn($q) => 
                $q->where('branch_name', $user->branch_name)
            );
            
        } elseif ($user->hasRole('user')) {
            // ✅ User: Only their own gudang
            $query->where('unique_id', $user->unique_id);
            
        } else {
            return response()->json([
                'status' => false, 
                'message' => 'Access denied - no valid role'
            ], 403);
        }

        // ✅ Additional filters
        if ($request->filled('search')) {
            $query->whereHas('barang', fn($q) => 
                $q->where('nama_barang', 'like', "%{$request->search}%")
            );
        }

        if ($request->filled('type')) {
            $query->where('tipe', $request->type);
        }
        
        $gudang = $query->paginate(20);
        return GudangResource::collection($gudang);
    }

    public function store(Request $request)
    {
        // ✅ Add authorization check
        $this->authorize('create', Gudang::class);
        
        $user = Auth::user();
        
        $data = $request->validate([
            'unique_id'     => 'required|string|exists:tb_users,unique_id',
            'id_barang'     => 'required|string|exists:tb_barang,id_barang',
            'jumlah_barang' => 'required|integer|min:1',
            'keterangan'    => 'nullable|string|max:500',
            'tipe'          => 'sometimes|in:manual,biasa,mandiri',
        ]);

        // ✅ SECURITY: Users can only add to their own gudang
        if ($user->hasRole('user') && $data['unique_id'] !== $user->unique_id) {
            return response()->json([
                'status' => false,
                'message' => 'You can only add items to your own gudang'
            ], 403);
        }

        // ✅ SECURITY: Only admin can add to other users' gudang
        if (!$user->hasRole('admin') && $data['unique_id'] !== $user->unique_id) {
            return response()->json([
                'status' => false,
                'message' => 'Only admin can add items to other users gudang'
            ], 403);
        }

        // ✅ SECURITY: Manager can only add to their branch users
        if ($user->hasRole('manager') && $data['unique_id'] !== $user->unique_id) {
            $targetUser = \App\Models\User::where('unique_id', $data['unique_id'])->first();
            if (!$targetUser || $targetUser->branch_name !== $user->branch_name) {
                return response()->json([
                    'status' => false,
                    'message' => 'Manager can only add items to their branch users'
                ], 403);
            }
        }

        $data['tipe'] = $data['tipe'] ?? 'biasa';

        // ✅ Check if gudang entry already exists
        $existingGudang = Gudang::where('unique_id', $data['unique_id'])
                                ->where('id_barang', $data['id_barang'])
                                ->first();

        if ($existingGudang) {
            // ✅ Update existing entry
            $existingGudang->jumlah_barang += $data['jumlah_barang'];
            $existingGudang->keterangan = $data['keterangan'] ?? $existingGudang->keterangan;
            $existingGudang->save();
            
            return new GudangResource($existingGudang);
        } else {
            // ✅ Create new entry
            $gudang = Gudang::create($data);
            return (new GudangResource($gudang))
                ->response()
                ->setStatusCode(HttpResponse::HTTP_CREATED);
        }
    }

    public function show($unique_id, $id_barang)
    {
        $user = Auth::user();
        $query = Gudang::with(['user', 'barang'])
                      ->where('unique_id', $unique_id)
                      ->where('id_barang', $id_barang);

        // ✅ CRITICAL: Apply scope filtering before finding
        if ($user->hasRole('admin')) {
            // Admin can see any gudang entry
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

        $gudang = $query->firstOrFail();
        $this->authorize('view', $gudang);
        
        return new GudangResource($gudang);
    }

    public function update(Request $request, $unique_id, $id_barang)
    {
        $user = Auth::user();
        $query = Gudang::where('unique_id', $unique_id)
                      ->where('id_barang', $id_barang);

        // ✅ CRITICAL: Apply scope filtering before finding
        if ($user->hasRole('admin')) {
            // Admin can update any gudang entry
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

        $gudang = $query->firstOrFail();
        $this->authorize('update', $gudang);

        $data = $request->validate([
            'jumlah_barang' => 'sometimes|required|integer|min:0',
            'keterangan'    => 'nullable|string|max:500',
            'tipe'          => 'sometimes|in:manual,biasa,mandiri',
        ]);

        $gudang->update($data);
        return new GudangResource($gudang);
    }

    public function destroy($unique_id, $id_barang)
    {
        $user = Auth::user();
        $query = Gudang::where('unique_id', $unique_id)
                      ->where('id_barang', $id_barang);

        // ✅ CRITICAL: Apply scope filtering before finding
        if ($user->hasRole('admin')) {
            // Admin can delete any gudang entry
        } elseif ($user->hasRole('manager')) {
            // Manager: Only their branch (but typically can't delete)
            $query->whereHas('user', fn($q) => 
                $q->where('branch_name', $user->branch_name)
            );
        } elseif ($user->hasRole('user')) {
            // User: Only their own (but typically can't delete)
            $query->where('unique_id', $user->unique_id);
        } else {
            return response()->json([
                'status' => false, 
                'message' => 'Access denied'
            ], 403);
        }

        $gudang = $query->firstOrFail();
        $this->authorize('delete', $gudang);
        
        $gudang->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    public function adjustStock(Request $request, $unique_id, $id_barang)
    {
        $user = Auth::user();
        
        // ✅ Only admin can adjust stock
        if (!$user->hasRole('admin')) {
            return response()->json([
                'status' => false,
                'message' => 'Only admin can adjust stock'
            ], 403);
        }

        $gudang = Gudang::where('unique_id', $unique_id)
                       ->where('id_barang', $id_barang)
                       ->firstOrFail();

        $data = $request->validate([
            'adjustment_type' => 'required|in:add,subtract,set',
            'adjustment_amount' => 'required|integer|min:0',
            'reason' => 'required|string|max:500',
        ]);

        $oldStock = $gudang->jumlah_barang;

        switch ($data['adjustment_type']) {
            case 'add':
                $gudang->jumlah_barang += $data['adjustment_amount'];
                break;
            case 'subtract':
                $gudang->jumlah_barang = max(0, $gudang->jumlah_barang - $data['adjustment_amount']);
                break;
            case 'set':
                $gudang->jumlah_barang = $data['adjustment_amount'];
                break;
        }

        $gudang->keterangan = $data['reason'];
        $gudang->save();

        // ✅ Log the adjustment (you can create a StockAdjustment model for this)
        \Log::info('Stock adjustment made', [
            'admin_id' => $user->unique_id,
            'gudang_id' => $gudang->id,
            'old_stock' => $oldStock,
            'new_stock' => $gudang->jumlah_barang,
            'adjustment_type' => $data['adjustment_type'],
            'adjustment_amount' => $data['adjustment_amount'],
            'reason' => $data['reason']
        ]);

        return new GudangResource($gudang);
    }
}
