<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BatasBarang;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BatasBarangController extends Controller
{
    // GET /api/batas-barang
    public function index()
    {
        $batas = BatasBarang::all();
        return response()->json($batas, HttpResponse::HTTP_OK);
    }

    // POST /api/batas-barang
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_barang'   => 'required|string',
            'batas_barang'=> 'required|integer|min:0',
        ]);

        $batas = BatasBarang::create($data);
        return response()->json($batas, HttpResponse::HTTP_CREATED);
    }

    // GET /api/batas-barang/{id_barang}
    public function show($id_barang)
    {
        $batas = BatasBarang::findOrFail($id_barang);
        return response()->json($batas, HttpResponse::HTTP_OK);
    }

    // PUT/PATCH /api/batas-barang/{id_barang}
    public function update(Request $request, $id_barang)
    {
        $batas = BatasBarang::findOrFail($id_barang);
        $data = $request->validate([
            'batas_barang'=> 'sometimes|required|integer|min:0',
        ]);
        $batas->update($data);
        return response()->json($batas, HttpResponse::HTTP_OK);
    }

    // DELETE /api/batas-barang/{id_barang}
    public function destroy($id_barang)
    {
        $batas = BatasBarang::findOrFail($id_barang);
        $batas->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
