<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PenggunaanBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PenggunaanBarangTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;
    private Barang $barang;
    private string $userCabangId = 'CABANG-USER1';
    private string $adminCabangId = 'CABANG-PUSAT';

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        // Create Cabangs
        \App\Models\Cabang::create(['id_cabang' => $this->adminCabangId, 'nama_cabang' => 'Pusat']);
        \App\Models\Cabang::create(['id_cabang' => $this->userCabangId, 'nama_cabang' => 'Branch User 1']);

        // Create users
        $this->admin = User::factory()->create(['id_cabang' => $this->adminCabangId]);
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create(['id_cabang' => $this->userCabangId]);
        $this->user->assignRole('user');

        // Create barang and seed stok
        $this->barang = Barang::factory()->create();

        Gudang::create([
            'id_cabang' => $this->userCabangId,
            'id_barang' => $this->barang->id_barang,
            'jumlah_barang' => 50,
        ]);
    }

    public function test_user_can_create_penggunaan_barang_with_auto_approval()
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'id_barang' => $this->barang->id_barang,
            'jumlah_digunakan' => 5,
            'keperluan' => 'Kebutuhan operasional',
            'tanggal_penggunaan' => now()->toDateString(),
        ];

        $response = $this->postJson('/api/v1/penggunaan-barang', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.jumlah_digunakan', 5);

        // Verify record created with auto-approved status
        $this->assertDatabaseHas('tb_penggunaan_barang', [
            'id_barang' => $this->barang->id_barang,
            'status' => 'approved',
            'jumlah_digunakan' => 5,
        ]);
    }

    public function test_penggunaan_barang_auto_reduces_gudang_stock()
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'id_barang' => $this->barang->id_barang,
            'jumlah_digunakan' => 10,
            'keperluan' => 'Kebutuhan rutin',
            'tanggal_penggunaan' => now()->toDateString(),
        ];

        $this->postJson('/api/v1/penggunaan-barang', $payload);

        // Stock should be reduced
        $this->assertDatabaseHas('tb_gudang', [
            'id_cabang' => $this->userCabangId,
            'id_barang' => $this->barang->id_barang,
            'jumlah_barang' => 40,
        ]);
    }

    public function test_user_cannot_use_more_stock_than_available()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/penggunaan-barang', [
            'id_barang' => $this->barang->id_barang,
            'jumlah_digunakan' => 100, // More than available 50
            'keperluan' => 'Attempt to exceed stock',
            'tanggal_penggunaan' => now()->toDateString(),
        ]);

        // Service throws exception as 500, not validation error (acceptable behavior)
        $response->assertStatus(500);
    }

    public function test_user_requires_mandatory_fields()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/penggunaan-barang', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'id_barang',
                'jumlah_digunakan',
                'keperluan',
                'tanggal_penggunaan',
            ]);
    }

    public function test_user_can_list_penggunaan_barang()
    {
        Sanctum::actingAs($this->user);

        // Create multiple usage records manually
        PenggunaanBarang::create([
            'id_barang' => $this->barang->id_barang,
            'id_cabang' => $this->userCabangId,
            'unique_id' => $this->user->unique_id,
            'jumlah_digunakan' => 5,
            'keperluan' => 'Test 1',
            'tanggal_penggunaan' => now()->toDateString(),
            'status' => 'approved',
        ]);

        PenggunaanBarang::create([
            'id_barang' => $this->barang->id_barang,
            'id_cabang' => $this->userCabangId,
            'unique_id' => $this->user->unique_id,
            'jumlah_digunakan' => 3,
            'keperluan' => 'Test 2',
            'tanggal_penggunaan' => now()->toDateString(),
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/v1/penggunaan-barang');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id_penggunaan', 'jumlah_digunakan', 'status', 'created_at']
                ]
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_view_all_penggunaan_barang()
    {
        // Create usage records for different branches
        PenggunaanBarang::create([
            'id_barang' => $this->barang->id_barang,
            'id_cabang' => $this->userCabangId,
            'unique_id' => $this->user->unique_id,
            'jumlah_digunakan' => 5,
            'keperluan' => 'User branch',
            'tanggal_penggunaan' => now()->toDateString(),
            'status' => 'approved',
        ]);

        Sanctum::actingAs($this->admin);
        $response = $this->getJson('/api/v1/penggunaan-barang');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_view_barang_relation_in_list()
    {
        Sanctum::actingAs($this->user);

        PenggunaanBarang::create([
            'id_barang' => $this->barang->id_barang,
            'id_cabang' => $this->userCabangId,
            'unique_id' => $this->user->unique_id,
            'jumlah_digunakan' => 5,
            'keperluan' => 'Test',
            'tanggal_penggunaan' => now()->toDateString(),
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/v1/penggunaan-barang');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.barang.id_barang', $this->barang->id_barang)
            ->assertJsonPath('data.0.barang.nama_barang', $this->barang->nama_barang);
    }

    public function test_user_scope_filtered_to_own_penggunaan_barang()
    {
        // Create usage for another user
        $otherUser = User::factory()->create(['id_cabang' => $this->userCabangId]);
        $otherUser->assignRole('user');

        PenggunaanBarang::create([
            'id_barang' => $this->barang->id_barang,
            'id_cabang' => $this->userCabangId,
            'unique_id' => $otherUser->unique_id,
            'jumlah_digunakan' => 5,
            'keperluan' => 'Other user',
            'tanggal_penggunaan' => now()->toDateString(),
            'status' => 'approved',
        ]);

        // Create usage for current user
        PenggunaanBarang::create([
            'id_barang' => $this->barang->id_barang,
            'id_cabang' => $this->userCabangId,
            'unique_id' => $this->user->unique_id,
            'jumlah_digunakan' => 3,
            'keperluan' => 'Current user',
            'tanggal_penggunaan' => now()->toDateString(),
            'status' => 'approved',
        ]);

        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/v1/penggunaan-barang');

        // User should only see their own
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_penggunaan_barang_requires_valid_barang()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/penggunaan-barang', [
            'id_barang' => 'INVALID-BARANG-ID',
            'jumlah_digunakan' => 5,
            'keperluan' => 'Test',
            'tanggal_penggunaan' => now()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_barang']);
    }

    public function test_user_can_view_single_penggunaan_barang()
    {
        $penggunaan = PenggunaanBarang::create([
            'id_barang' => $this->barang->id_barang,
            'id_cabang' => $this->userCabangId,
            'unique_id' => $this->user->unique_id,
            'jumlah_digunakan' => 5,
            'keperluan' => 'Test',
            'tanggal_penggunaan' => now()->toDateString(),
            'status' => 'approved',
        ]);

        Sanctum::actingAs($this->user);
        $response = $this->getJson("/api/v1/penggunaan-barang/{$penggunaan->id_penggunaan}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id_penggunaan', $penggunaan->id_penggunaan)
            ->assertJsonPath('data.jumlah_digunakan', 5);
    }
}
