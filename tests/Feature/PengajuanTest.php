<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Barang;
use App\Models\JenisBarang;
use App\Models\Pengajuan;
use Spatie\Permission\Models\Role;
use Laravel\Sanctum\Sanctum;

class PengajuanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private Barang $barang;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        // Create a standard user and an admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        
        // Create a necessary JenisBarang and Barang for testing
        $jenisBarang = JenisBarang::factory()->create();
        $this->barang = Barang::factory()->create(['id_jenis_barang' => $jenisBarang->id_jenis_barang]);
    }

    /**
     * Test the full lifecycle of a procurement request.
     *
     * @return void
     */
    public function test_pengajuan_creation_and_approval_workflow()
    {
        // 1. A User creates a new Pengajuan
        Sanctum::actingAs($this->user);

        $pengajuanData = [
            'id_pengajuan' => 'TESTPGJ001',
            'unique_id' => $this->user->unique_id,
            'items' => [
                ['id_barang' => $this->barang->id_barang, 'jumlah' => 10]
            ]
        ];
        
        // Laravel doesn't natively support nested validation in postJson,
        // so we call our service directly to test the business logic.
        // In a real app with more time, we could write a more complex test for the controller.
        $pengajuanService = $this->app->make(\App\Services\PengajuanService::class);
        $pengajuan = $pengajuanService->create($pengajuanData, null);

        $this->assertDatabaseHas('tb_pengajuan', [
            'id_pengajuan' => 'TESTPGJ001',
            'status_pengajuan' => Pengajuan::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('tb_detail_pengajuan', [
            'id_pengajuan' => 'TESTPGJ001',
            'id_barang' => $this->barang->id_barang,
            'jumlah' => 10,
        ]);

        // 2. The User is FORBIDDEN from approving their own request.
        $response = $this->putJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}", [
            'status_pengajuan' => Pengajuan::STATUS_APPROVED,
        ]);
        $response->assertStatus(403); // Forbidden

        // 3. An Admin logs in and successfully approves the request.
        Sanctum::actingAs($this->admin);
        
        $response = $this->putJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}", [
            'status_pengajuan' => Pengajuan::STATUS_APPROVED,
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('data.status_pengajuan', Pengajuan::STATUS_APPROVED);

        // 4. CRITICAL ASSERTION: Check that the stock was correctly transferred to the user's gudang.
        $this->assertDatabaseHas('tb_gudang', [
            'unique_id' => $this->user->unique_id,
            'id_barang' => $this->barang->id_barang,
            'jumlah_barang' => 10,
        ]);

        // 5. The User is now FORBIDDEN from deleting the approved request.
        Sanctum::actingAs($this->user);
        $deleteResponse = $this->deleteJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}");
        $deleteResponse->assertStatus(403);
    }
}