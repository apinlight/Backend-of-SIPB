<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BatasPengajuanResource;
use App\Models\BatasPengajuan;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BatasPengajuanController extends Controller
{
    use AuthorizesRequests;

    // GET /api/batas-pengajuan
    public function index()
    {
        $this->authorize('viewAny', BatasPengajuan::class);

        $batas = BatasPengajuan::all();
        return BatasPengajuanResource::collection($batas)
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }

    // POST /api/batas-pengajuan
    public function store(Request $request)
    {
        $this->authorize('create', BatasPengajuan::class);

        $data = $request->validate([
            'id_barang'        => 'required|string|exists:tb_barang,id_barang',
            'batas_pengajuan'  => 'required|integer|min:0',
        ]);

        $batas = BatasPengajuan::create($data);
        return (new BatasPengajuanResource($batas))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    // GET /api/batas-pengajuan/{id_barang}    
    public function show($id_barang)
    {
        $batas = BatasPengajuan::findOrFail($id_barang);
        $this->authorize('view', $batas);

        return (new BatasPengajuanResource($batas))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }

    // PUT/PATCH /api/batas-pengajuan/{id_barang}
    public function update(Request $request, $id_barang)
    {
        $batas = BatasPengajuan::findOrFail($id_barang);
        $this->authorize('update', $batas);

        $data = $request->validate([
            'batas_pengajuan'  => 'sometimes|required|integer|min:0',
        ]);

        $batas->update($data);
        return (new BatasPengajuanResource($batas))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }

    // DELETE /api/batas-pengajuan/{id_barang}
    public function destroy($id_barang)
    {
        $batas = BatasPengajuan::findOrFail($id_barang);
        $this->authorize('delete', $batas);

        $batas->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
