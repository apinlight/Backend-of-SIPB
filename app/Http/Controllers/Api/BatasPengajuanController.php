<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BatasPengajuan;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BatasPengajuanController extends Controller
{
    // GET /api/batas-pengajuan
    public function index()
    {
        $batas = BatasPengajuan::all();
        return response()->json($batas, HttpResponse::HTTP_OK);
    }

    // POST /api/batas-pengajuan
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_barang'        => 'required|string',
            'batas_pengajuan'  => 'required|integer|min:0',
        ]);

        $batas = BatasPengajuan::create($data);
        return response()->json($batas, HttpResponse::HTTP_CREATED);
    }

    // GET /api/batas-pengajuan/{id_barang}
    public function show($id_barang)
    {
        $batas = BatasPengajuan::findOrFail($id_barang);
        return response()->json($batas, HttpResponse::HTTP_OK);
    }

    // PUT/PATCH /api/batas-pengajuan/{id_barang}
    public function update(Request $request, $id_barang)
    {
        $batas = BatasPengajuan::findOrFail($id_barang);
        $data = $request->validate([
            'batas_pengajuan'  => 'sometimes|required|integer|min:0',
        ]);
        $batas->update($data);
        return response()->json($batas, HttpResponse::HTTP_OK);
    }

    // DELETE /api/batas-pengajuan/{id_barang}
    public function destroy($id_barang)
    {
        $batas = BatasPengajuan::findOrFail($id_barang);
        $batas->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
