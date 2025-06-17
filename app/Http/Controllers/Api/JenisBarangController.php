<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JenisBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JenisBarangController extends Controller
{
    public function index(Request $request)
    {
        $query = JenisBarang::query();

        if ($request->filled('search')) {
            $query->where('nama_jenis_barang', 'like', "%{$request->search}%");
        }

        $jenisBarang = $query->paginate(15);

        return response()->json([
            'status' => true,
            'data' => $jenisBarang->items(),
            'meta' => [
                'current_page' => $jenisBarang->currentPage(),
                'last_page' => $jenisBarang->lastPage(),
                'total' => $jenisBarang->total(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->hasRole('admin')) {
            return response()->json(['status' => false, 'message' => 'Only admin can create categories'], 403);
        }

        $request->validate([
            'nama_jenis_barang' => 'required|string|max:255|unique:jenis_barang',
            'deskripsi' => 'nullable|string'
        ]);

        $jenisBarang = JenisBarang::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Jenis barang created successfully',
            'data' => $jenisBarang
        ]);
    }

    public function update(Request $request, int $id)
    {
        if (!Auth::user()->hasRole('admin')) {
            return response()->json(['status' => false, 'message' => 'Only admin can update categories'], 403);
        }

        $request->validate([
            'nama_jenis_barang' => 'required|string|max:255|unique:jenis_barang,nama_jenis_barang,' . $id,
            'deskripsi' => 'nullable|string'
        ]);

        $jenisBarang = JenisBarang::findOrFail($id);
        $jenisBarang->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Jenis barang updated successfully',
            'data' => $jenisBarang
        ]);
    }

    public function destroy(int $id)
    {
        if (!Auth::user()->hasRole('admin')) {
            return response()->json(['status' => false, 'message' => 'Only admin can delete categories'], 403);
        }

        $jenisBarang = JenisBarang::findOrFail($id);
        
        // Check if jenis barang is being used
        if ($jenisBarang->barang()->exists()) {
            return response()->json([
                'status' => false, 
                'message' => 'Cannot delete category that has items'
            ], 400);
        }

        $jenisBarang->delete();

        return response()->json([
            'status' => true,
            'message' => 'Jenis barang deleted successfully'
        ]);
    }
}
