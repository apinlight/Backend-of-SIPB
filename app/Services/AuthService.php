<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private function getTokenExpiryMinutes(): int
    {
        return (int) (config('sanctum.expiration', 1440));
    }

    public function login(array $credentials, string $ip): array
    {
        $loginType = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::with(['roles', 'cabang'])->where($loginType, $credentials['login'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => ['The provided credentials are incorrect.']]);
        }
        if (isset($user->is_active) && ! $user->is_active) {
            throw ValidationException::withMessages(['login' => ['Account is deactivated. Please contact an administrator.']]);
        }
        if (config('auth.single_session', false)) {
            $user->tokens()->delete();
        }
        $user->update(['last_login_at' => now(), 'last_login_ip' => $ip]);

        return $this->createTokenResponse($user);
    }



    public function refreshToken(User $user): array
    {
        if (isset($user->is_active) && ! $user->is_active) {
            if ($token = $user->currentAccessToken) {
                $token->delete();
            }
            throw new \Exception('Account is deactivated.');
        }

        if ($token = $user->currentAccessToken) {
            $token->delete();
        }

        return $this->createTokenResponse($user->fresh(['roles', 'cabang']));
    }

    public function changePassword(User $user, array $passwords): void
    {
        if (! Hash::check($passwords['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => ['The provided password does not match your current password.']]);
        }
        // ✅ FIX: No need to manually Hash::make(). The model's mutator handles it.
        $user->update(['password' => $passwords['new_password']]);
        $user->tokens()->delete();
    }

    private function createTokenResponse(User $user): array
    {
        $expiryMinutes = $this->getTokenExpiryMinutes();
        $expiry = now()->addMinutes($expiryMinutes);
        $token = $user->createToken('auth-token', ['*'], $expiry)->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expiryMinutes * 60,
            'expires_at' => $expiry->toISOString(),
        ];
    }
}
