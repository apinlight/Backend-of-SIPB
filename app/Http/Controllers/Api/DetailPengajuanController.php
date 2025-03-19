<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailPengajuan;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DetailPengajuanController extends Controller
{
    /**
     * Display a listing of all detail pengajuan records.
     *
     * GET /api/detail-pengajuan
     */
    public function index()
    {
        $details = DetailPengajuan::with(['pengajuan', 'barang'])->get();
        return response()->json($details, HttpResponse::HTTP_OK);
    }

    /**
     * Store a newly created detail pengajuan record in storage.
     *
     * POST /api/detail-pengajuan
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_pengajuan' => 'required|string',
            'id_barang'    => 'required|string'
        ]);

        // Create a new record. 
        // (If needed, you can check for duplicates or use updateOrCreate.)
        $detail = DetailPengajuan::create($data);
        return response()->json($detail, HttpResponse::HTTP_CREATED);
    }

    /**
     * Display the specified detail pengajuan record.
     *
     * GET /api/detail-pengajuan/{id_pengajuan}/{id_barang}
     */
    public function show($id_pengajuan, $id_barang)
    {
        $detail = DetailPengajuan::where('id_pengajuan', $id_pengajuan)
            ->where('id_barang', $id_barang)
            ->with(['pengajuan', 'barang'])
            ->firstOrFail();
        return response()->json($detail, HttpResponse::HTTP_OK);
    }

    /**
     * Update is not supported in this example since there are no extra fields 
     * to update beyond the composite keys.
     *
     * PUT/PATCH /api/detail-pengajuan/{id_pengajuan}/{id_barang}
     */
    public function update(Request $request, $id_pengajuan, $id_barang)
    {
        // Since our table only contains the two key columns,
        // there's no additional data to update.
        return response()->json([
            'message' => 'No updateable fields available for detail pengajuan.'
        ], HttpResponse::HTTP_OK);
    }

    /**
     * Remove the specified detail pengajuan record from storage.
     *
     * DELETE /api/detail-pengajuan/{id_pengajuan}/{id_barang}
     */
    public function destroy($id_pengajuan, $id_barang)
    {
        $detail = DetailPengajuan::where('id_pengajuan', $id_pengajuan)
            ->where('id_barang', $id_barang)
            ->firstOrFail();
        $detail->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
