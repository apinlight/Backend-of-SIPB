<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExportWordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure roles exist for Spatie permission
        $this->seed(RoleSeeder::class);
    }

    public function test_summary_word_export_downloads_docx(): void
    {
    /** @var User $admin */
    $admin = User::factory()->create();
        $admin->assignRole('admin');

        Sanctum::actingAs($admin, ['*']);
        $response = $this->get('/api/v1/laporan/export-word/summary?start_date=2025-01-01&end_date=2025-01-31');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertStringContainsString('attachment', strtolower($response->headers->get('content-disposition', '')));
    }

    public function test_barang_word_export_downloads_docx(): void
    {
    /** @var User $admin */
    $admin = User::factory()->create();
        $admin->assignRole('admin');

        Sanctum::actingAs($admin, ['*']);
        $response = $this->get('/api/v1/laporan/export-word/barang?start_date=2025-01-01&end_date=2025-01-31');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertStringContainsString('attachment', strtolower($response->headers->get('content-disposition', '')));
    }
}
