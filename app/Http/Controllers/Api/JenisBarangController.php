<?php

// app/Http/Controllers/Api/JenisBarangController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JenisBarangResource;
use App\Models\JenisBarang;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class JenisBarangController extends Controller
{
    public function index()
    {
        return JenisBarangResource::collection(JenisBarang::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_jenis_barang' => 'required|string'
        ]);

        $jenis = JenisBarang::create($data);
        return new JenisBarangResource($jenis);
    }

    public function show($id)
    {
        $jenis = JenisBarang::findOrFail($id);
        return new JenisBarangResource($jenis);
    }

    public function update(Request $request, $id)
    {
        $jenis = JenisBarang::findOrFail($id);

        $data = $request->validate([
            'nama_jenis_barang' => 'required|string'
        ]);

        $jenis->update($data);
        return new JenisBarangResource($jenis);
    }

    public function destroy($id)
    {
        $jenis = JenisBarang::findOrFail($id);
        $jenis->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
