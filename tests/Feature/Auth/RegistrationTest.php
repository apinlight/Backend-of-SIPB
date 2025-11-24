<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

#[Group('legacy')]
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure roles exist for assignment.
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);
    }

    /**
     * Test that a user can be successfully registered.
     */
    public function test_user_can_be_registered()
    {
        // Enable registration for this test
        config(['auth.allow_registration' => true]);

        $userData = [
            'unique_id' => 'USER001',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'branch_name' => 'Main Branch',
        ];

        // Act: Send a POST request to the register endpoint.
        $response = $this->postJson('/api/v1/register', $userData);

        // Assert: Check for a successful response and data structure.
        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'user' => ['unique_id', 'username', 'email', 'roles'],
                'token',
            ])
            ->assertJson([
                'status' => true,
                'message' => 'Registration successful',
                'user' => [
                    'email' => 'test@example.com',
                    'roles' => ['user'], // Assert the default role was assigned
                ],
            ]);

        // Assert that the user was actually created in the database.
        $this->assertDatabaseHas('tb_users', [
            'email' => 'test@example.com',
            'username' => 'testuser',
        ]);
    }

    /**
     * Test that registration fails when the feature is disabled.
     */
    public function test_registration_is_disabled_by_config()
    {
        // Arrange: Explicitly disable registration in the config for this test.
        config(['auth.allow_registration' => false]);

        $userData = [
            'unique_id' => 'USER001',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'branch_name' => 'Main Branch',
        ];

        // Act
        $response = $this->postJson('/api/v1/register', $userData);

        // Assert
        $response->assertStatus(403); // It should be forbidden.
    }

    /**
     * Test that validation fails for required fields.
     */
    public function test_validation_fails_if_data_is_missing()
    {
        config(['auth.allow_registration' => true]);

        $response = $this->postJson('/api/v1/register', [
            'username' => 'testuser',
            // Email and password are intentionally missing
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'unique_id', 'branch_name']);
    }
}
