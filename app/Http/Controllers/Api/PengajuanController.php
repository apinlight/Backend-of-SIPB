<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PengajuanResource;
use App\Models\Pengajuan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PengajuanController extends Controller
{
    use AuthorizesRequests;
    // GET /api/pengajuan
    public function index()
    {
        $this->authorize('viewAny', Pengajuan::class);

        $pengajuan = Pengajuan::with('user', 'details')->get();
        return PengajuanResource::collection($pengajuan);
    }

    // POST /api/pengajuan
    public function store(Request $request)
    {
        $this->authorize('create', Pengajuan::class);

        $data = $request->validate([
            'id_pengajuan'    => 'required|string',
            'unique_id'       => 'required|string',
            'status_pengajuan'=> 'required|in:Menunggu Persetujuan,Disetujui,Ditolak',
        ]);

        $pengajuan = Pengajuan::create($data);
        return (new PengajuanResource($pengajuan))
        ->response()
        ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    // GET /api/pengajuan/{id_pengajuan}
    public function show($id_pengajuan)
    {
        $pengajuan = Pengajuan::with('user', 'details')
            ->where('id_pengajuan', $id_pengajuan)
            ->firstOrFail();
            
        $this->authorize('view', $pengajuan);

        return (new PengajuanResource($pengajuan))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }

    // PUT/PATCH /api/pengajuan/{id_pengajuan}
    public function update(Request $request, $id_pengajuan)
    {
        $pengajuan = Pengajuan::where('id_pengajuan', $id_pengajuan)->firstOrFail();

        $this->authorize('update', $pengajuan);

        $data = $request->validate([
            'status_pengajuan'=> 'sometimes|required|in:Menunggu Persetujuan,Disetujui,Ditolak',
        ]);

        $pengajuan->update($data);
        return (new PengajuanResource($pengajuan))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }

    // DELETE /api/pengajuan/{id_pengajuan}
    public function destroy($id_pengajuan)
    {
        $pengajuan = Pengajuan::where('id_pengajuan', $id_pengajuan)->firstOrFail();

        $this->authorize('delete', $pengajuan); 
        
        $pengajuan->delete();
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
