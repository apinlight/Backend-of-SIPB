<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PengajuanCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Barang $barang;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        $this->barang = Barang::factory()->create();
    }

    public function test_user_can_create_pengajuan_without_explicit_unique_id_and_id_pengajuan()
    {
        Sanctum::actingAs($this->user);

        $payload = [
            // id_pengajuan omitted intentionally to test auto-generation
            // unique_id omitted (should be injected server-side)
            'tipe_pengajuan' => 'biasa',
            'keterangan' => 'Permintaan penambahan stok',
            'items' => [
                [
                    'id_barang' => $this->barang->id_barang,
                    'jumlah' => 5,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/pengajuan', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.status_pengajuan', Pengajuan::STATUS_PENDING)
            ->assertJsonPath('data.unique_id', $this->user->unique_id)
            ->assertJsonStructure([
                'data' => [
                    'id_pengajuan',
                    'unique_id',
                    'status_pengajuan',
                    'tipe_pengajuan',
                    'keterangan',
                    'details',
                ],
            ]);

        // Assert record actually created with injected unique_id
        $this->assertDatabaseHas('tb_pengajuan', [
            'unique_id' => $this->user->unique_id,
            'status_pengajuan' => Pengajuan::STATUS_PENDING,
        ]);
    }

    public function test_user_cannot_set_status_on_create_directly_to_approved()
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'tipe_pengajuan' => 'biasa',
            'status_pengajuan' => Pengajuan::STATUS_APPROVED, // Should be overridden / rejected (creation starts pending)
            'items' => [
                [
                    'id_barang' => $this->barang->id_barang,
                    'jumlah' => 2,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/pengajuan', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('data.status_pengajuan', Pengajuan::STATUS_PENDING);
    }
}
