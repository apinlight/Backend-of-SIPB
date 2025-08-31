<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    private function getTokenExpiryMinutes(): int
    {
        return (int) (config('sanctum.expiration', 1440));
    }

    /**
     * Authenticate a user and generate a token.
     */
    public function login(array $credentials, string $ip): array
    {
        $loginType = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::with('roles')->where($loginType, $credentials['login'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (isset($user->is_active) && !$user->is_active) {
            // Use a custom exception or a ValidationException
            throw ValidationException::withMessages([
                'login' => ['Account is deactivated. Please contact an administrator.'],
            ]);
        }

        if (config('auth.single_session', false)) {
            $user->tokens()->delete();
        }

        $user->update(['last_login_at' => now(), 'last_login_ip' => $ip]);

        return $this->createTokenResponse($user);
    }

    /**
     * Register a new user and generate a token.
     */
    public function register(array $data): array
    {
        $user = User::create([
            'unique_id'   => $data['unique_id'],
            'username'    => $data['username'],
            'email'       => $data['email'],
            'password'    => Hash::make($data['password']),
            'branch_name' => $data['branch_name'],
            'is_active'   => true,
        ]);

        $user->assignRole('user');
        $user->load('roles');

        return $this->createTokenResponse($user);
    }

    /**
     * Create a new token for a user.
     */
    public function refreshToken(User $user): array
    {
        if (isset($user->is_active) && !$user->is_active) {
            $user->currentAccessToken()->delete();
            throw new \Exception('Account is deactivated.');
        }

        $user->currentAccessToken()->delete();
        return $this->createTokenResponse($user->fresh('roles'));
    }

    /**
     * Change a user's password.
     */
    public function changePassword(User $user, array $passwords): void
    {
        if (!Hash::check($passwords['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        $user->update(['password' => Hash::make($passwords['new_password'])]);
        $user->tokens()->delete(); // Force re-login on all devices for security
    }

    /**
     * Helper to generate the token and build the response array.
     */
    private function createTokenResponse(User $user): array
    {
        $expiryMinutes = $this->getTokenExpiryMinutes();
        $expiry = now()->addMinutes($expiryMinutes);
        $token = $user->createToken('auth-token', ['*'], $expiry)->plainTextToken;

        return [
            'user'       => $user,
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expiryMinutes * 60,
            'expires_at' => $expiry->toISOString(),
        ];
    }
}