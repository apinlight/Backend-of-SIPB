<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CabangResource;
use App\Models\Cabang;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CabangController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Cabang::class);

        $cabang = Cabang::withCount(['users', 'gudang'])
            ->orderBy('created_at', 'desc')
            ->get();

        return CabangResource::collection($cabang);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Cabang::class);

        $validated = $request->validate([
            'nama_cabang' => 'required|string|max:255|unique:tb_cabang,nama_cabang',
        ]);

        $cabang = Cabang::create([
            'id_cabang' => (string) Str::ulid(),
            'nama_cabang' => $validated['nama_cabang'],
        ]);

        return new CabangResource($cabang);
    }

    public function show(string $id)
    {
        $cabang = Cabang::withCount(['users', 'gudang'])->findOrFail($id);
        $this->authorize('view', $cabang);

        return new CabangResource($cabang);
    }

    public function update(Request $request, string $id)
    {
        $cabang = Cabang::findOrFail($id);
        $this->authorize('update', $cabang);

        $validated = $request->validate([
            'nama_cabang' => 'required|string|max:255|unique:tb_cabang,nama_cabang,' . $id . ',id_cabang',
        ]);

        $cabang->update($validated);

        return new CabangResource($cabang);
    }

    public function destroy(string $id)
    {
        $cabang = Cabang::findOrFail($id);
        $this->authorize('delete', $cabang);

        // Check if cabang has users
        if ($cabang->users()->count() > 0) {
            return response()->json([
                'message' => 'Tidak dapat menghapus cabang yang masih memiliki pengguna'
            ], 422);
        }

        $cabang->delete();

        return response()->json([
            'message' => 'Cabang berhasil dihapus'
        ]);
    }
}
