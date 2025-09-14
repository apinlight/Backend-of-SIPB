<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Barang;
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
        
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        
        $this->barang = Barang::factory()->create();
    }

    public function test_pengajuan_creation_and_approval_workflow()
    {
        // 1. A User creates a new Pengajuan via the API endpoint
        Sanctum::actingAs($this->user);

        $pengajuanData = [
            'id_pengajuan' => 'TESTPGJ001',
            'unique_id' => $this->user->unique_id,
            'items' => [
                ['id_barang' => $this->barang->id_barang, 'jumlah' => 10]
            ]
        ];
        
        $createResponse = $this->postJson("/api/v1/pengajuan", $pengajuanData);
        $createResponse->assertStatus(201);
        $this->assertDatabaseHas('tb_pengajuan', ['id_pengajuan' => 'TESTPGJ001']);

        // 2. The User is FORBIDDEN from approving their own request.
        $response = $this->putJson("/api/v1/pengajuan/TESTPGJ001", [
            'status_pengajuan' => Pengajuan::STATUS_APPROVED,
        ]);
        $response->assertStatus(403);

        // 3. An Admin logs in and successfully approves the request.
        Sanctum::actingAs($this->admin);
        
        $response = $this->putJson("/api/v1/pengajuan/TESTPGJ001", [
            'status_pengajuan' => Pengajuan::STATUS_APPROVED,
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('data.status_pengajuan', Pengajuan::STATUS_APPROVED);

        // 4. CRITICAL: Check that the stock was correctly transferred to the user's gudang.
        $this->assertDatabaseHas('tb_gudang', [
            'unique_id' => $this->user->unique_id,
            'id_barang' => $this->barang->id_barang,
            'jumlah_barang' => 10,
        ]);

        // 5. The User is FORBIDDEN from deleting the approved request.
        Sanctum::actingAs($this->user);
        $deleteResponse = $this->deleteJson("/api/v1/pengajuan/TESTPGJ001");
        $deleteResponse->assertStatus(403);
    }
}