<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;

class ApiRoutesTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles for testing
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    }

    /**
     * Test the API is online.
     */
    public function test_api_is_online(): void
    {
        $response = $this->get('/api/v1');
        $response->assertStatus(200)
                 ->assertJson(['message' => 'API is online']);
    }

    /**
     * Test protected routes require authentication.
     */
    public function test_protected_routes_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/barang');
        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can access barang.
     */
    public function test_authenticated_user_can_access_barang(): void
    {
        // Create a user
        $user = User::factory()->create();
        
        // Create a token for the user
        $token = $user->createToken('test-token')->plainTextToken;
        
        // Make request with token
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/v1/barang');
        
        $response->assertStatus(200);
    }

    /**
     * Test barang CRUD operations.
     */
    public function test_barang_crud_operations(): void
    {
        // Create Jenis Barang
        $jenisBarang = \App\Models\JenisBarang::factory()->create();

        // Create a user
        $user = User::factory()->create();
        
        // Create a token for the user
        $token = $user->createToken('test-token')->plainTextToken;

        
        
        // Create
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/v1/barang', [
                             'id_barang' => 'BRG001',
                             'nama_barang' => 'Test Barang',
                             'id_jenis_barang' => $jenisBarang->id_jenis_barang,
                         ]);
        $response->assertStatus(201);

        // Read
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/v1/barang/BRG001');
        $response->assertStatus(200)
                 ->assertJson(['nama_barang' => 'Test Barang']);

        // Update
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->putJson('/api/v1/barang/BRG001', [
                             'nama_barang' => 'Updated Barang',
                         ]);
        $response->assertStatus(200)
                 ->assertJson(['nama_barang' => 'Updated Barang']);

        // Delete
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->deleteJson('/api/v1/barang/BRG001');
        $response->assertStatus(204);
    }

    /**
     * Test only admin can create users.
     */
    public function test_only_admin_can_create_users(): void
    {
        // Regular User
        $user = User::factory()->user()->create();
        $userToken = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $userToken)
                         ->postJson('/api/v1/users', [
                             'unique_id' => 'USR002',
                             'username' => 'testuser',
                             'password' => 'password',
                             'branch_name' => 'Test Branch',
                             'role' => 'user',
                         ]);

        $response->assertStatus(403);

        // Admin User
        $admin = User::factory()->admin()->create();
        $adminToken = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                         ->postJson('/api/v1/users', [
                             'unique_id' => 'USR002',
                             'username' => 'testuser',
                             'password' => 'password',
                             'branch_name' => 'Test Branch',
                             'role' => 'user',
                         ]);

        $response->assertStatus(201);
    }
}
