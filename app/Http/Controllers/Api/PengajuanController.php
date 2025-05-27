<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PengajuanResource;
use App\Models\Pengajuan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PengajuanController extends Controller
{
    use AuthorizesRequests;
    // GET /api/pengajuan
    public function index()
    {
        $this->authorize('viewAny', Pengajuan::class);

        $pengajuan = Pengajuan::with('user', 'details')->paginate(20);
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
            'tipe_pengajuan'  => 'sometimes|in:manual,biasa',
        ]);
        $data['tipe_pengajuan'] = $data['tipe_pengajuan'] ?? 'biasa';

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

    if (
        isset($data['status_pengajuan']) &&
        $data['status_pengajuan'] === 'Disetujui' &&
        $pengajuan->tipe_pengajuan === 'biasa'
        ) {
        DB::transaction(function () use ($pengajuan) {
            foreach ($pengajuan->details as $detail) {
                // Kurangi stok admin pusat
                $adminGudang = \App\Models\Gudang::where('unique_id', 'ADMIN001')
                    ->where('id_barang', $detail->id_barang)
                    ->first();
                if ($adminGudang) {
                    $adminGudang->jumlah_barang -= $detail->jumlah;
                    $adminGudang->save();
                }
                // Tambah stok user
                $userGudang = \App\Models\Gudang::firstOrCreate(
                    ['unique_id' => $pengajuan->unique_id, 'id_barang' => $detail->id_barang],
                    ['jumlah_barang' => 0]
                );
                $userGudang->jumlah_barang += $detail->jumlah;
                $userGudang->save();
            }
            $pengajuan->status_pengajuan = 'Disetujui';
            $pengajuan->save();
        });
        return (new PengajuanResource($pengajuan->fresh()))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }

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
