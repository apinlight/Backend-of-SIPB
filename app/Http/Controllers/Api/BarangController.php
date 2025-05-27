<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BarangResource;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BarangController extends Controller
{
    use AuthorizesRequests;

    // GET /api/barang
    public function index()
    {
        $this->authorize('viewAny', Barang::class);
        $barang = Barang::with('jenisBarang')->paginate(20);
        return BarangResource::collection($barang);

        //debug
        //$barang = Barang::paginate(20);
        // $barang = Barang::with('jenisBarang')->first();
        // return response()->json(['test' => 'ok']);
        // return response()->json($barang);
    }

    // POST /api/barang
    public function store(Request $request)
    {
        $this->authorize('create', Barang::class);

        $data = $request->validate([
            //'id_barang'        => 'required|string',
            'nama_barang'      => 'required|string',
            'id_jenis_barang'  => 'required|string|exists:tb_jenis_barang,id_jenis_barang',
            'harga_barang'     => 'nullable|integer',
        ]);

        $barang = Barang::create($data);
        $barang->load('jenisBarang');

        return new BarangResource($barang);
    }

    // GET /api/barang/{id_barang}
    public function show($id_barang)
    {
        $barang = Barang::with('jenisBarang')->findOrFail($id_barang);
        $this->authorize('view', $barang);

        return new BarangResource($barang);
    }

    // PUT/PATCH /api/barang/{id_barang}
    public function update(Request $request, $id_barang)
    {
        $barang = Barang::findOrFail($id_barang);
        $this->authorize('update', $barang);

        $data = $request->validate([
            'nama_barang'      => 'sometimes|string',
            'id_jenis_barang'  => 'sometimes|string|exists:tb_jenis_barang,id_jenis_barang',
            'harga_barang'     => 'nullable|integer',
        ]);

        $barang->update($data);
        $barang->load('jenisBarang');

        return new BarangResource($barang);
    }

    // DELETE /api/barang/{id_barang}
    public function destroy($id_barang)
    {
        $barang = Barang::findOrFail($id_barang);
        $this->authorize('delete', $barang);
        
        $barang->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
