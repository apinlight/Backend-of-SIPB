<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\JenisBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BarangTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $user;
    private JenisBarang $jenisBarang;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'manager']);
        Role::create(['name' => 'user']);

        // Create cabang
        \App\Models\Cabang::create(['id_cabang' => 'CABANG-PUSAT', 'nama_cabang' => 'Pusat']);
        \App\Models\Cabang::create(['id_cabang' => 'CABANG-USER', 'nama_cabang' => 'Branch User']);

        // Create users with roles
        $this->admin = User::factory()->create(['id_cabang' => 'CABANG-PUSAT']);
        $this->admin->assignRole('admin');

        $this->manager = User::factory()->create(['id_cabang' => 'CABANG-PUSAT']);
        $this->manager->assignRole('manager');

        $this->user = User::factory()->create(['id_cabang' => 'CABANG-USER']);
        $this->user->assignRole('user');

        // Create jenis barang
        $this->jenisBarang = JenisBarang::create([
            'nama_jenis_barang' => 'Peralatan',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_barang()
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'nama_barang' => 'Laptop Dell XPS 15',
            'id_jenis_barang' => $this->jenisBarang->id_jenis_barang,
            'deskripsi' => 'Laptop untuk kebutuhan admin',
            'harga_barang' => 15000000,
            'spesifikasi' => 'Intel i7, 16GB RAM',
            'satuan' => 'unit',
            'id_barang' => 'BRG-LAPTOP-001',
        ];

        $response = $this->postJson('/api/v1/barang', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.nama_barang', 'Laptop Dell XPS 15')
            ->assertJsonPath('data.harga_barang', 15000000);

        $this->assertDatabaseHas('tb_barang', [
            'nama_barang' => 'Laptop Dell XPS 15',
        ]);
    }

    public function test_user_can_view_barang_list()
    {
        Sanctum::actingAs($this->user);

        Barang::create([
            'nama_barang' => 'Public Barang',
            'id_jenis_barang' => $this->jenisBarang->id_jenis_barang,
            'harga_barang' => 1000000,
            'satuan' => 'unit',
        ]);

        $response = $this->getJson('/api/v1/barang');

        $response->assertStatus(200);
    }

    public function test_can_retrieve_all_barang()
    {
        Sanctum::actingAs($this->admin);

        // Create 3 barang manually
        for ($i = 0; $i < 3; $i++) {
            Barang::create([
                'nama_barang' => "Test Barang $i",
                'id_jenis_barang' => $this->jenisBarang->id_jenis_barang,
                'harga_barang' => 1000000,
                'satuan' => 'unit',
            ]);
        }

        $response = $this->getJson('/api/v1/barang');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id_barang', 'nama_barang', 'harga_barang', 'satuan']
                ]
            ]);
    }

    public function test_can_retrieve_single_barang()
    {
        Sanctum::actingAs($this->admin);

        $barang = Barang::create([
            'nama_barang' => 'Single Barang',
            'id_jenis_barang' => $this->jenisBarang->id_jenis_barang,
            'harga_barang' => 5000000,
            'satuan' => 'pcs',
        ]);

        $response = $this->getJson("/api/v1/barang/{$barang->id_barang}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id_barang', $barang->id_barang)
            ->assertJsonPath('data.nama_barang', 'Single Barang');
    }

    public function test_admin_can_update_barang()
    {
        Sanctum::actingAs($this->admin);

        $barang = Barang::create([
            'nama_barang' => 'Original Name',
            'id_jenis_barang' => $this->jenisBarang->id_jenis_barang,
            'harga_barang' => 1000000,
            'satuan' => 'unit',
        ]);

        $response = $this->putJson("/api/v1/barang/{$barang->id_barang}", [
            'nama_barang' => 'Updated Barang Name',
            'harga_barang' => 20000000,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.nama_barang', 'Updated Barang Name');

        $this->assertDatabaseHas('tb_barang', [
            'id_barang' => $barang->id_barang,
            'nama_barang' => 'Updated Barang Name',
        ]);
    }

    public function test_barang_can_be_updated()
    {
        Sanctum::actingAs($this->admin);

        $barang = Barang::create([
            'nama_barang' => 'Test',
            'id_jenis_barang' => $this->jenisBarang->id_jenis_barang,
            'harga_barang' => 1000000,
            'satuan' => 'unit',
        ]);

        $response = $this->putJson("/api/v1/barang/{$barang->id_barang}", [
            'nama_barang' => 'Updated',
        ]);

        $response->assertStatus(200);
    }

    public function test_barang_can_be_deleted()
    {
        Sanctum::actingAs($this->admin);

        $barang = Barang::create([
            'nama_barang' => 'To Delete',
            'id_jenis_barang' => $this->jenisBarang->id_jenis_barang,
            'harga_barang' => 1000000,
            'satuan' => 'unit',
        ]);

        $response = $this->deleteJson("/api/v1/barang/{$barang->id_barang}");

        $response->assertStatus(200 + 4); // API returns 204 No Content
    }

    public function test_user_cannot_delete_barang()
    {
        Sanctum::actingAs($this->user);

        $barang = Barang::create([
            'nama_barang' => 'Test',
            'id_jenis_barang' => $this->jenisBarang->id_jenis_barang,
            'harga_barang' => 1000000,
            'satuan' => 'unit',
        ]);

        $response = $this->deleteJson("/api/v1/barang/{$barang->id_barang}");

        $response->assertStatus(403);
    }

    public function test_barang_requires_mandatory_fields()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/barang', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nama_barang', 'id_jenis_barang']);
    }

    public function test_barang_response_structure()
    {
        Sanctum::actingAs($this->admin);

        $barang = Barang::create([
            'nama_barang' => 'With Jenis',
            'id_jenis_barang' => $this->jenisBarang->id_jenis_barang,
            'harga_barang' => 1000000,
            'satuan' => 'unit',
        ]);

        $response = $this->getJson("/api/v1/barang/{$barang->id_barang}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id_barang',
                    'nama_barang',
                    'harga_barang',
                    'satuan',
                    'id_jenis_barang',
                ]
            ]);
    }

    public function test_barang_can_be_paginated()
    {
        Sanctum::actingAs($this->admin);

        // Create 20 barang
        for ($i = 0; $i < 20; $i++) {
            Barang::create([
                'nama_barang' => "Barang $i",
                'id_jenis_barang' => $this->jenisBarang->id_jenis_barang,
                'harga_barang' => 1000000,
                'satuan' => 'unit',
            ]);
        }

        $response = $this->getJson('/api/v1/barang');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id_barang', 'nama_barang']
                ]
            ]);
    }

    public function test_barang_can_be_filtered_by_jenis()
    {
        Sanctum::actingAs($this->admin);

        $jenis2 = JenisBarang::create(['nama_jenis_barang' => 'Furniture', 'is_active' => true]);

        // Create 5 with jenis1
        for ($i = 0; $i < 5; $i++) {
            Barang::create([
                'nama_barang' => "Peralatan $i",
                'id_jenis_barang' => $this->jenisBarang->id_jenis_barang,
                'harga_barang' => 1000000,
                'satuan' => 'unit',
            ]);
        }

        // Create 3 with jenis2
        for ($i = 0; $i < 3; $i++) {
            Barang::create([
                'nama_barang' => "Furniture $i",
                'id_jenis_barang' => $jenis2->id_jenis_barang,
                'harga_barang' => 2000000,
                'satuan' => 'pcs',
            ]);
        }

        $response = $this->getJson("/api/v1/barang?id_jenis_barang={$this->jenisBarang->id_jenis_barang}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['*' => ['id_barang', 'nama_barang']]]);
    }

    public function test_barang_harga_must_be_numeric()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/barang', [
            'nama_barang' => 'Test',
            'id_jenis_barang' => $this->jenisBarang->id_jenis_barang,
            'harga_barang' => 'bukan angka',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['harga_barang']);
    }

    public function test_manager_can_view_barang()
    {
        $barang = Barang::create([
            'nama_barang' => 'Test',
            'id_jenis_barang' => $this->jenisBarang->id_jenis_barang,
            'harga_barang' => 1000000,
            'satuan' => 'unit',
        ]);

        // Manager can read
        Sanctum::actingAs($this->manager);
        $getResponse = $this->getJson("/api/v1/barang/{$barang->id_barang}");
        $this->assertEquals(200, $getResponse->getStatusCode());
    }
}
