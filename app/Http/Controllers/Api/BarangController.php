<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BarangResource;
use App\Models\Barang;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BarangController extends Controller
{
    // GET /api/barang
    public function index()
    {
        return BarangResource::collection(Barang::with('jenisBarang')->get());
    }

    // POST /api/barang
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_barang'   => 'required|string',
            'nama_barang' => 'required|string',
            'id_jenis_barang' => 'required|string|exists:tb_jenis_barang,id_jenis_barang',
            'harga_barang' => 'nullable|integer',
        ]);

        $barang = Barang::create($data);
        $barang->load('jenisBarang');
        return new BarangResource($barang);
    }

    // GET /api/barang/{id_barang}
    public function show($id_barang)
    {
        $barang = Barang::findOrFail($id_barang);
        return new BarangResource($barang);
    }

    // PUT/PATCH /api/barang/{id_barang}
    public function update(Request $request, $id_barang)
    {
        $barang = Barang::findOrFail($id_barang);
        $data = $request->validate([
            'nama_barang' => 'sometimes|string',
            'id_jenis_barang'  => 'sometimes|string|exists:tb_jenis_barang,id_jenis_barang',
            'harga_barang' => 'nullable|integer',
        ]);

        $barang->update($data);
        $barang->load('jenisBarang');
        return new BarangResource($barang);
    }

    // DELETE /api/barang/{id_barang}
    public function destroy($id_barang)
    {
        $barang = Barang::findOrFail($id_barang);
        $barang->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
