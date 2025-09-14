<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;

class LoginTest extends TestCase
{
    use RefreshDatabase; // This trait automatically creates a fresh, in-memory database for each test.

    /**
     * A basic setup method to create roles before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Create the roles that our application uses.
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'manager']);
        Role::create(['name' => 'user']);
    }

    /**
     * Test a successful login with correct credentials.
     *
     * @return void
     */
    public function test_user_can_login_with_correct_credentials()
    {
        // 1. Arrange: Create a user in our test database.
        $user = User::factory()->create([
            'password' => 'password123', // We set a known password.
        ]);
        $user->assignRole('user');

        // 2. Act: Send a POST request to the login endpoint.
        $response = $this->postJson('/api/v1/login', [
            'login' => $user->email,
            'password' => 'password123',
        ]);

        // 3. Assert: Check that everything worked as expected.
        $response
            ->assertStatus(200) // It should return a 200 OK status.
            ->assertJsonStructure([ // The JSON response should have this structure.
                'status',
                'message',
                'user' => [
                    'unique_id',
                    'username',
                    'email',
                ],
                'token',
                'token_type',
                'expires_in',
                'expires_at',
            ])
            ->assertJson([ // The response should contain this specific data.
                'status' => true,
                'message' => 'Login successful',
                'user' => [
                    'unique_id' => $user->unique_id,
                    'email' => $user->email,
                ],
                'token_type' => 'Bearer',
            ]);
    }

    /**
     * Test that login fails with an incorrect password.
     *
     * @return void
     */
    public function test_user_cannot_login_with_incorrect_password()
    {
        // 1. Arrange
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        // 2. Act
        $response = $this->postJson('/api/v1/login', [
            'login' => $user->email,
            'password' => 'wrong-password',
        ]);

        // 3. Assert
        $response
            ->assertStatus(422) // It should return a 422 Unprocessable Entity status.
            ->assertJsonValidationErrors(['login']); // It should have a validation error on the 'login' field.
    }

    /**
     * Test that login fails if the password is not provided.
     *
     * @return void
     */
    public function test_password_is_required_for_login()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/login', [
            'login' => $user->email,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']); // It should have a validation error on the 'password' field.
    }
}