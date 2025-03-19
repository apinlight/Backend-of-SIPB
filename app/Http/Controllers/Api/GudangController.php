<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class GudangController extends Controller
{
    // GET /api/gudang
    public function index()
    {
        $gudang = Gudang::with(['user', 'barang'])->get();
        return response()->json($gudang, HttpResponse::HTTP_OK);
    }

    // POST /api/gudang
    public function store(Request $request)
    {
        $data = $request->validate([
            'unique_id'    => 'required|string',
            'id_barang'    => 'required|string',
            'jumlah_barang'=> 'required|integer|min:0',
        ]);

        $gudang = Gudang::create($data);
        return response()->json($gudang, HttpResponse::HTTP_CREATED);
    }

    // GET /api/gudang/{unique_id}/{id_barang}
    public function show($unique_id, $id_barang)
    {
        $gudang = Gudang::where('unique_id', $unique_id)
                        ->where('id_barang', $id_barang)
                        ->with(['user', 'barang'])
                        ->firstOrFail();
        return response()->json($gudang, HttpResponse::HTTP_OK);
    }

    // PUT/PATCH /api/gudang/{unique_id}/{id_barang}
    public function update(Request $request, $unique_id, $id_barang)
    {
        $gudang = Gudang::where('unique_id', $unique_id)
                        ->where('id_barang', $id_barang)
                        ->firstOrFail();
        $data = $request->validate([
            'jumlah_barang'=> 'sometimes|required|integer|min:0',
        ]);

        $gudang->update($data);
        return response()->json($gudang, HttpResponse::HTTP_OK);
    }

    // DELETE /api/gudang/{unique_id}/{id_barang}
    public function destroy($unique_id, $id_barang)
    {
        $gudang = Gudang::where('unique_id', $unique_id)
                        ->where('id_barang', $id_barang)
                        ->firstOrFail();
        $gudang->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
