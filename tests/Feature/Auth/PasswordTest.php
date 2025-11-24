<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test a user can successfully change their password with valid credentials.
     */
    public function test_user_can_change_password_successfully()
    {
        // 1. Arrange: Create a user with a known password and a token.
        $user = User::factory()->create(['password' => 'old-password-123']);
        $user->createToken('test-token'); // Create an initial token to represent a session.

        // Authenticate as this user for the API request.
        Sanctum::actingAs($user);

        // 2. Act: Send a request to change the password.
        $response = $this->postJson('/api/v1/change-password', [
            'current_password' => 'old-password-123',
            'new_password' => 'new-safe-password-456',
            'new_password_confirmation' => 'new-safe-password-456',
        ]);

        // 3. Assert: Check for a successful response and correct behavior.
        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Password changed successfully. Please login again with your new password.',
            ]);

        // Assert that the password in the database has actually been changed.
        $this->assertTrue(Hash::check('new-safe-password-456', $user->fresh()->password));

        // CRITICAL SECURITY ASSERTION: Assert that all old tokens were revoked.
        $this->assertCount(0, $user->tokens);
    }

    /**
     * Test password change fails with incorrect current password.
     */
    public function test_password_change_fails_with_incorrect_current_password()
    {
        // 1. Arrange
        $user = User::factory()->create(['password' => 'old-password-123']);
        Sanctum::actingAs($user);

        // 2. Act
        $response = $this->postJson('/api/v1/change-password', [
            'current_password' => 'wrong-current-password', // This is incorrect
            'new_password' => 'new-safe-password-456',
            'new_password_confirmation' => 'new-safe-password-456',
        ]);

        // 3. Assert
        $response
            ->assertStatus(422) // Should be a validation error.
            ->assertJsonValidationErrors(['current_password']);
    }

    /**
     * Test password change fails if confirmation does not match.
     */
    public function test_password_change_fails_if_confirmation_does_not_match()
    {
        // 1. Arrange
        $user = User::factory()->create(['password' => 'old-password-123']);
        Sanctum::actingAs($user);

        // 2. Act
        $response = $this->postJson('/api/v1/change-password', [
            'current_password' => 'old-password-123',
            'new_password' => 'new-safe-password-456',
            'new_password_confirmation' => 'this-does-not-match', // Mismatch
        ]);

        // 3. Assert
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']); // Laravel reports this on the main password field.
    }
}
