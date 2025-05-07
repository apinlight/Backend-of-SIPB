<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BatasBarangResource;
use App\Models\BatasBarang;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BatasBarangController extends Controller
{
    use AuthorizesRequests;

    // GET /api/batas-barang
    public function index()
    {
        $this->authorize('viewAny', BatasBarang::class);

        $batas = BatasBarang::all();
        return BatasBarangResource::collection($batas)
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }

    // POST /api/batas-barang
    public function store(Request $request)
    {
        $this->authorize('create', BatasBarang::class);

        $data = $request->validate([
            'id_barang'   => 'required|string',
            'batas_barang'=> 'required|integer|min:0',
        ]);

        $batas = BatasBarang::create($data);
        return (new BatasBarangResource($batas))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    // GET /api/batas-barang/{id_barang}
    public function show($id_barang)
    {
        $batas = BatasBarang::findOrFail($id_barang);
        $this->authorize('view', $batas);

        return (new BatasBarangResource($batas))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }

    // PUT/PATCH /api/batas-barang/{id_barang}
    public function update(Request $request, $id_barang)
    {
        $batas = BatasBarang::findOrFail($id_barang);
        $this->authorize('update', $batas);

        $data = $request->validate([
            'batas_barang'=> 'sometimes|required|integer|min:0',
        ]);
        $batas->update($data);
        return (new BatasBarangResource($batas))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }

    // DELETE /api/batas-barang/{id_barang}
    public function destroy($id_barang)
    {
        $batas = BatasBarang::findOrFail($id_barang);
        $this->authorize('delete', $batas);

        $batas->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
