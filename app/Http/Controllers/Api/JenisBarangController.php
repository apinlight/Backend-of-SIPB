<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JenisBarangResource;
use App\Models\JenisBarang;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class JenisBarangController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        // ✅ All authenticated users can view jenis barang
        $this->authorize('viewAny', JenisBarang::class);
        
        $query = JenisBarang::query();

        // ✅ Apply filters
        if ($request->filled('search')) {
            $query->where('nama_jenis_barang', 'like', "%{$request->search}%");
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $jenisBarang = $query->paginate(20);
        return JenisBarangResource::collection($jenisBarang);
    }

    public function store(Request $request)
    {
        // ✅ Only admin can create jenis barang
        $this->authorize('create', JenisBarang::class);
        
        $data = $request->validate([
            'id_jenis_barang'   => 'required|string|unique:tb_jenis_barang,id_jenis_barang',
            'nama_jenis_barang' => 'required|string|max:255|unique:tb_jenis_barang,nama_jenis_barang',
            'deskripsi'         => 'nullable|string|max:1000',
            'is_active'         => 'sometimes|boolean',
        ]);

        $data['is_active'] = $data['is_active'] ?? true;

        $jenisBarang = JenisBarang::create($data);
        return (new JenisBarangResource($jenisBarang))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show($id_jenis_barang)
    {
        $jenisBarang = JenisBarang::where('id_jenis_barang', $id_jenis_barang)->firstOrFail();
        $this->authorize('view', $jenisBarang);
        
        return new JenisBarangResource($jenisBarang);
    }

    public function update(Request $request, $id_jenis_barang)
    {
        $jenisBarang = JenisBarang::where('id_jenis_barang', $id_jenis_barang)->firstOrFail();
        $this->authorize('update', $jenisBarang);

        $data = $request->validate([
            'nama_jenis_barang' => 'sometimes|required|string|max:255|unique:tb_jenis_barang,nama_jenis_barang,' . $jenisBarang->id_jenis_barang . ',id_jenis_barang',
            'deskripsi'         => 'nullable|string|max:1000',
            'is_active'         => 'sometimes|boolean',
        ]);

        $jenisBarang->update($data);
        return new JenisBarangResource($jenisBarang);
    }

    public function destroy($id_jenis_barang)
    {
        $jenisBarang = JenisBarang::where('id_jenis_barang', $id_jenis_barang)->firstOrFail();
        $this->authorize('delete', $jenisBarang);

        // ✅ Check if jenis barang is used by any barang
        $barangCount = $jenisBarang->barang()->count();
        if ($barangCount > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete jenis barang that is used by existing barang'
            ], 422);
        }

        $jenisBarang->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    public function toggleStatus($id_jenis_barang)
    {
        $jenisBarang = JenisBarang::where('id_jenis_barang', $id_jenis_barang)->firstOrFail();
        $this->authorize('update', $jenisBarang);

        $jenisBarang->is_active = !$jenisBarang->is_active;
        $jenisBarang->save();

        return new JenisBarangResource($jenisBarang);
    }

    public function active()
    {
        // ✅ All authenticated users can view active jenis barang
        $this->authorize('viewAny', JenisBarang::class);
        
        $jenisBarang = JenisBarang::where('is_active', true)->get();
        return JenisBarangResource::collection($jenisBarang);
    }
}
