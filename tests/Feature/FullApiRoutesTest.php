<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class FullApiRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    protected function loginAsAdmin()
    {
        $this->get('/sanctum/csrf-cookie');
        $admin = User::factory()->admin()->create([
            'username' => 'superadmin',
            'password' => 'password123',
        ]);
        $this->post('/api/login', [
            'login' => 'superadmin',
            'password' => 'password123',
        ])->assertStatus(200);
        return $admin;
    }

    protected function loginAsUser()
    {
        $this->get('/sanctum/csrf-cookie');
        $user = User::factory()->user()->create([
            'username' => 'user1',
            'password' => 'password123',
            'branch_name' => 'A',
        ]);
        $this->post('/api/login', [
            'login' => 'user1',
            'password' => 'password123',
        ])->assertStatus(200);
        return $user;
    }

    public function test_crud_jenis_barang()
    {
        $this->loginAsAdmin();

        // Create
        $jenisResponse = $this->post('/api/v1/jenis-barang', [
            'nama_jenis_barang' => 'Elektronik',
        ]);
        $jenisResponse->assertStatus(201);
        $jenis = $jenisResponse->json();
        $jenisId = $jenis['data']['id'] ?? $jenis['id'] ?? null;

        // Read
        $jenisShow = $this->get("/api/v1/jenis-barang/{$jenisId}");
        $jenisShow->assertStatus(200);

        // Update
        $jenisUpdate = $this->put("/api/v1/jenis-barang/{$jenisId}", [
            'nama_jenis_barang' => 'Elektronik Update',
        ]);
        $jenisUpdate->assertStatus(200);

        // Delete
        $jenisDelete = $this->delete("/api/v1/jenis-barang/{$jenisId}");
        $jenisDelete->assertStatus(204);
    }

    public function test_crud_barang()
    {
        $this->loginAsAdmin();

        // Buat jenis barang dulu
        $jenis = $this->post('/api/v1/jenis-barang', [
            'nama_jenis_barang' => 'Elektronik',
        ])->json();
        $jenisId = $jenis['data']['id'] ?? $jenis['id'] ?? $jenis['data']['id_jenis_barang'] ?? $jenis['id_jenis_barang'] ?? null;
        $this->assertNotNull($jenisId, 'id_jenis_barang tidak ditemukan di response');

        // Create
        $barangResponse = $this->post('/api/v1/barang', [
            'id_barang' => 'BRG001',
            'nama_barang' => 'Laptop',
            'id_jenis_barang' => $jenisId,
            'harga_barang' => 1000000,
        ]);
        $barangResponse->assertStatus(201);
        $barangData = $barangResponse->json();
        $idBarang = $barangData['data']['id_barang'] ?? $barangData['id_barang'] ?? 'BRG001';

        // Read
        $barangShow = $this->get("/api/v1/barang/{$idBarang}");
        $barangShow->assertStatus(200);

        // Update
        $barangUpdate = $this->put("/api/v1/barang/{$idBarang}", [
            'nama_barang' => 'Laptop Update',
        ]);
        $barangUpdate->assertStatus(200);

        // Delete
        $barangDelete = $this->delete("/api/v1/barang/{$idBarang}");
        $barangDelete->assertStatus(204);
    }

public function test_crud_pengajuan()
{
    // User creates pengajuan
    $user = $this->loginAsUser();

    $pengajuanResponse = $this->post('/api/v1/pengajuan', [
        'id_pengajuan' => 'PNG001',
        'unique_id' => $user->unique_id,
        'status_pengajuan' => 'Menunggu Persetujuan',
    ]);
    $pengajuanResponse->assertStatus(201);

    // User can update while status is 'Menunggu Persetujuan'
    $pengajuanUpdate = $this->put('/api/v1/pengajuan/PNG001', [
        'status_pengajuan' => 'Menunggu Persetujuan',
    ]);
    $pengajuanUpdate->assertStatus(200);

    // User cannot approve/deny
    $pengajuanDeny = $this->put('/api/v1/pengajuan/PNG001', [
        'status_pengajuan' => 'Ditolak',
    ]);
    $pengajuanDeny->assertStatus(403);

    // Admin approves pengajuan
    $this->loginAsAdmin();
    $pengajuanApprove = $this->put('/api/v1/pengajuan/PNG001', [
        'status_pengajuan' => 'Disetujui',
    ]);
    $pengajuanApprove->assertStatus(200);

    // User cannot update after approved
    $this->loginAsUser();
    $pengajuanUpdateAfterApprove = $this->put('/api/v1/pengajuan/PNG001', [
        'status_pengajuan' => 'Menunggu Persetujuan',
    ]);
    $pengajuanUpdateAfterApprove->assertStatus(403);

    // Admin can delete
    $this->loginAsAdmin();
    $pengajuanDelete = $this->delete('/api/v1/pengajuan/PNG001');
    $pengajuanDelete->assertStatus(204);
}

    public function test_list_barang()
    {
        $this->loginAsAdmin();
        $jenis = $this->post('/api/v1/jenis-barang', [
            'nama_jenis_barang' => 'Elektronik',
        ])->json();
        $jenisId = $jenis['data']['id'] ?? $jenis['id'] ?? $jenis['data']['id_jenis_barang'] ?? $jenis['id_jenis_barang'] ?? null;
        $this->post('/api/v1/barang', [
            'id_barang' => 'BRG008',
            'nama_barang' => 'Tablet',
            'id_jenis_barang' => $jenisId,
            'harga_barang' => 2000000,
        ]);
        $response = $this->get('/api/v1/barang');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_list_pengajuan()
    {
        $user = $this->loginAsUser();
        $this->post('/api/v1/pengajuan', [
            'id_pengajuan' => 'PNG002',
            'unique_id' => $user->unique_id,
            'status_pengajuan' => 'Menunggu Persetujuan',
        ]);
        $response = $this->get('/api/v1/pengajuan');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_list_gudang()
    {
        $admin = $this->loginAsAdmin();
        $jenis = $this->post('/api/v1/jenis-barang', [
            'nama_jenis_barang' => 'Elektronik',
        ])->json();
        $jenisId = $jenis['data']['id'] ?? $jenis['id'] ?? $jenis['data']['id_jenis_barang'] ?? $jenis['id_jenis_barang'] ?? null;
        $barang = $this->post('/api/v1/barang', [
            'id_barang' => 'BRG009',
            'nama_barang' => 'Kamera',
            'id_jenis_barang' => $jenisId,
            'harga_barang' => 3000000,
        ])->json();
        $idBarang = $barang['data']['id_barang'] ?? $barang['id_barang'] ?? 'BRG009';
        $this->post('/api/v1/gudang', [
            'unique_id' => $admin->unique_id,
            'id_barang' => $idBarang,
            'jumlah_barang' => 7,
        ]);
        $response = $this->get('/api/v1/gudang');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    // --- Tambahan test: Tidak boleh akses resource yang tidak ada ---

    public function test_show_nonexistent_barang()
    {
        $this->loginAsAdmin();
        $response = $this->get('/api/v1/barang/TIDAKADA');
        $response->assertStatus(404);
    }

    public function test_update_nonexistent_barang()
    {
        $this->loginAsAdmin();
        $response = $this->put('/api/v1/barang/TIDAKADA', [
            'nama_barang' => 'Tidak Ada',
        ]);
        $response->assertStatus(404);
    }

    public function test_delete_nonexistent_barang()
    {
        $this->loginAsAdmin();
        $response = $this->delete('/api/v1/barang/TIDAKADA');
        $response->assertStatus(404);
    }

    // --- Tambahan test: Tidak boleh create barang/gudang tanpa login ---

    public function test_guest_cannot_create_barang()
    {
        $response = $this->post('/api/v1/barang', [
            'id_barang' => 'BRG010',
            'nama_barang' => 'Mouse',
            'id_jenis_barang' => 1,
            'harga_barang' => 10000,
        ]);
        $response->assertStatus(401);
    }

    public function test_guest_cannot_create_gudang()
    {
        $response = $this->post('/api/v1/gudang', [
            'unique_id' => 'guest',
            'id_barang' => 'BRG010',
            'jumlah_barang' => 1,
        ]);
        $response->assertStatus(401);
    }

    public function test_crud_gudang()
    {
        $admin = $this->loginAsAdmin();

        // Buat jenis barang dulu
        $jenis = $this->post('/api/v1/jenis-barang', [
            'nama_jenis_barang' => 'Elektronik',
        ])->json();
        $jenisId = $jenis['data']['id'] ?? $jenis['id'] ?? $jenis['data']['id_jenis_barang'] ?? $jenis['id_jenis_barang'] ?? null;
        $this->assertNotNull($jenisId, 'id_jenis_barang tidak ditemukan di response');

        // Buat barang dulu dan ambil id_barang dari response
        $barang = $this->post('/api/v1/barang', [
            'id_barang' => 'BRG003',
            'nama_barang' => 'Printer',
            'id_jenis_barang' => $jenisId,
            'harga_barang' => 700000,
        ])->json();
        $idBarang = $barang['data']['id_barang'] ?? $barang['id_barang'] ?? 'BRG003';

        // Create
        $gudangResponse = $this->post('/api/v1/gudang', [
            'unique_id' => $admin->unique_id,
            'id_barang' => $idBarang,
            'jumlah_barang' => 5,
        ]);
        $gudangResponse->assertStatus(201);

        // Read
        $gudangShow = $this->get("/api/v1/gudang/{$admin->unique_id}/{$idBarang}");
        $gudangShow->assertStatus(200);

        // Update
        $gudangUpdate = $this->put("/api/v1/gudang/{$admin->unique_id}/{$idBarang}", [
            'jumlah_barang' => 10,
        ]);
        $gudangUpdate->assertStatus(200);

        // Delete
        $gudangDelete = $this->delete("/api/v1/gudang/{$admin->unique_id}/{$idBarang}");
        $gudangDelete->assertStatus(204);
    }

    public function test_logout()
    {
        $this->loginAsAdmin();
        $logout = $this->post('/api/logout');
        $logout->assertStatus(200);
    }
}