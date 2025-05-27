<?php

// app/Http/Controllers/Api/JenisBarangController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JenisBarangResource;
use App\Models\JenisBarang;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class JenisBarangController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        return JenisBarangResource::collection(JenisBarang::paginate(20));
    }

    public function store(Request $request)
    {
        $this->authorize('create', JenisBarang::class);

        $data = $request->validate([
            'nama_jenis_barang' => 'required|string'
        ]);

        $jenis = JenisBarang::create($data);
        return new JenisBarangResource($jenis);
    }

    public function show($id)
    {
        $jenis = JenisBarang::findOrFail($id);

        $this->authorize('view', $jenis);

        return new JenisBarangResource($jenis);
    }

    public function update(Request $request, $id)
    {
        $jenis = JenisBarang::findOrFail($id);

        $this->authorize('update', $jenis);

        $data = $request->validate([
            'nama_jenis_barang' => 'required|string'
        ]);

        $jenis->update($data);
        return new JenisBarangResource($jenis);
    }

    public function destroy($id)
    {
        $jenis = JenisBarang::findOrFail($id);

        $this->authorize('delete', $jenis);
        
        $jenis->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
