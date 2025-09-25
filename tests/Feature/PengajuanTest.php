<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Barang;
use App\Models\Gudang;
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

        Gudang::create([
            'unique_id' => $this->admin->unique_id,
            'id_barang' => $this->barang->id_barang,
            'jumlah_barang' => 100,
        ]);
    }

    public function test_admin_can_approve_a_users_pengajuan_and_stock_is_transferred()
    {
        Sanctum::actingAs($this->user);
        $pengajuan = Pengajuan::factory()->create([
            'unique_id' => $this->user->unique_id,
            'status_pengajuan' => Pengajuan::STATUS_PENDING,
        ]);
        $pengajuan->details()->create([
            'id_barang' => $this->barang->id_barang,
            'jumlah' => 10,
        ]);

        Sanctum::actingAs($this->admin);
        
        $response = $this->putJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}", [
            'status_pengajuan' => Pengajuan::STATUS_APPROVED,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status_pengajuan', Pengajuan::STATUS_APPROVED);

        $this->assertDatabaseHas('tb_gudang', [
            'unique_id' => $this->user->unique_id,
            'id_barang' => $this->barang->id_barang,
            'jumlah_barang' => 10,
        ]);
        
        $this->assertDatabaseHas('tb_gudang', [
            'unique_id' => $this->admin->unique_id,
            'id_barang' => $this->barang->id_barang,
            'jumlah_barang' => 90,
        ]);
    }

    public function test_a_user_is_forbidden_from_approving_their_own_pengajuan()
    {
        Sanctum::actingAs($this->user);
        $pengajuan = Pengajuan::factory()->create(['unique_id' => $this->user->unique_id]);

        $response = $this->putJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}", [
            'status_pengajuan' => Pengajuan::STATUS_APPROVED,
        ]);
        
        $response->assertStatus(403);
    }
}