<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

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

        // Create Cabang first to satisfy FK constraint on tb_users.id_cabang
        \App\Models\Cabang::create(['id_cabang' => 'CABANG-PUSAT', 'nama_cabang' => 'Pusat']);
        \App\Models\Cabang::create(['id_cabang' => 'CABANG-USER1', 'nama_cabang' => 'Branch User 1']);

        // Create users and assign branches via factory
        $this->admin = User::factory()->create(['id_cabang' => 'CABANG-PUSAT']);
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create(['id_cabang' => 'CABANG-USER1']);
        $this->user->assignRole('user');

        $this->barang = Barang::factory()->create();

        // Seed initial central stock (admin/pusat branch)
        Gudang::create([
            'id_cabang' => $this->admin->id_cabang,
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
            'id_cabang' => $this->user->id_cabang,
            'id_barang' => $this->barang->id_barang,
            'jumlah_barang' => 10,
        ]);

        $this->assertDatabaseHas('tb_gudang', [
            'id_cabang' => $this->admin->id_cabang,
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
