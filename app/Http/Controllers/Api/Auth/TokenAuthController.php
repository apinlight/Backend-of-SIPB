<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class TokenAuthController extends Controller
{
    /**
     * ✅ Get token expiry minutes with proper fallback
     */
    private function getTokenExpiryMinutes(): int
    {
        return (int) (config('sanctum.expiration') ?? 1440); // ✅ FIXED: Proper null coalescing
    }

    /**
     * ✅ STATELESS Token-based login with enhanced security
     */
    public function login(Request $request): JsonResponse
    {
        // ✅ Rate limiting for login attempts (using cache, not session)
        $key = 'login_attempts:' . $request->ip();
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= 5) {
            $lockoutTime = Cache::get($key . ':lockout', 0);
            if ($lockoutTime > now()->timestamp) {
                $remainingSeconds = $lockoutTime - now()->timestamp;
                return response()->json([
                    'status' => false,
                    'message' => "Too many login attempts. Please try again in {$remainingSeconds} seconds."
                ], 429);
            }
        }

        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // ✅ Determine login type (email or username)
        $loginType = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        // ✅ Load user with roles relationship
        $user = User::with('roles')->where($loginType, $credentials['login'])->first();

        // ✅ Check credentials
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            // ✅ Increment failed attempts (stateless)
            $newAttempts = $attempts + 1;
            Cache::put($key, $newAttempts, now()->addMinutes(15));
            
            if ($newAttempts >= 5) {
                Cache::put($key . ':lockout', now()->addMinutes(15)->timestamp, now()->addMinutes(15));
            }
            
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        // ✅ Check if user is active
        if (isset($user->is_active) && !$user->is_active) {
            return response()->json([
                'status' => false,
                'message' => 'Account is deactivated. Please contact administrator.'
            ], 403);
        }

        // ✅ Clear failed attempts on successful login
        Cache::forget($key);
        Cache::forget($key . ':lockout');

        // ✅ Delete old tokens (optional - for single session per user)
        if (config('auth.single_session', false)) {
            $user->tokens()->delete();
        }

        // ✅ Create new token with expiration (stateless)
        $tokenExpiryMinutes = $this->getTokenExpiryMinutes();
        $tokenExpiry = now()->addMinutes($tokenExpiryMinutes); // ✅ FIXED: Use method result
        $token = $user->createToken('auth-token', ['*'], $tokenExpiry)->plainTextToken;

        // ✅ Update last login info (optional tracking)
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip()
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $tokenExpiryMinutes * 60, // Convert to seconds
            'expires_at' => $tokenExpiry->toISOString()
        ], 200);
    }

    /**
     * ✅ STATELESS Token-based register with enhanced validation
     */
    public function register(Request $request): JsonResponse
    {
        // ✅ Check if registration is enabled
        if (!config('auth.allow_registration', false)) {
            return response()->json([
                'status' => false,
                'message' => 'Registration is currently disabled'
            ], 403);
        }

        // ✅ Rate limiting for registration (stateless)
        $key = 'register_attempts:' . $request->ip();
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= 3) {
            $lockoutTime = Cache::get($key . ':lockout', 0);
            if ($lockoutTime > now()->timestamp) {
                $remainingSeconds = $lockoutTime - now()->timestamp;
                return response()->json([
                    'status' => false,
                    'message' => "Too many registration attempts. Please try again in {$remainingSeconds} seconds."
                ], 429);
            }
        }

        $data = $request->validate([
            'unique_id' => 'required|string|unique:tb_users,unique_id',
            'username' => 'required|string|max:255|unique:tb_users,username|regex:/^[a-zA-Z0-9_]+$/',
            'email' => 'required|string|email|max:255|unique:tb_users,email',
            'password' => 'required|string|min:8|confirmed',
            'branch_name' => 'required|string|max:255',
        ]);

        try {
            // ✅ Create user
            $user = User::create([
                'unique_id' => $data['unique_id'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'branch_name' => $data['branch_name'],
                'is_active' => true,
            ]);

            // ✅ Assign default role and load relationships
            $user->assignRole('user');
            $user->load('roles');

            // ✅ Create token (stateless)
            $tokenExpiryMinutes = $this->getTokenExpiryMinutes();
            $tokenExpiry = now()->addMinutes($tokenExpiryMinutes); // ✅ FIXED: Use method result
            $token = $user->createToken('auth-token', ['*'], $tokenExpiry)->plainTextToken;

            // ✅ Clear registration attempts on success
            Cache::forget($key);
            Cache::forget($key . ':lockout');

            return response()->json([
                'status' => true,
                'message' => 'Registration successful',
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => $tokenExpiryMinutes * 60,
                'expires_at' => $tokenExpiry->toISOString()
            ], 201);

        } catch (\Exception $e) {
            // ✅ Increment failed registration attempts
            $newAttempts = $attempts + 1;
            Cache::put($key, $newAttempts, now()->addMinutes(15));
            
            if ($newAttempts >= 3) {
                Cache::put($key . ':lockout', now()->addMinutes(15)->timestamp, now()->addMinutes(15));
            }
            
            return response()->json([
                'status' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ✅ STATELESS Refresh token with security checks
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // ✅ Check if user is still active
        if (isset($user->is_active) && !$user->is_active) {
            // ✅ Delete current token if user is deactivated
            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'status' => false,
                'message' => 'Account is deactivated'
            ], 403);
        }

        // ✅ Load fresh user data with roles (stateless)
        $user = $user->fresh(['roles']);

        // ✅ Delete current token
        $request->user()->currentAccessToken()->delete();

        // ✅ Create new token (stateless)
        $tokenExpiryMinutes = $this->getTokenExpiryMinutes();
        $tokenExpiry = now()->addMinutes($tokenExpiryMinutes); // ✅ FIXED: Use method result
        $token = $user->createToken('auth-token', ['*'], $tokenExpiry)->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Token refreshed successfully',
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $tokenExpiryMinutes * 60,
            'expires_at' => $tokenExpiry->toISOString()
        ], 200);
    }

    /**
     * ✅ STATELESS Logout - revoke current token
     */
    public function logout(Request $request): JsonResponse
    {
        // ✅ Delete current access token (stateless)
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logout successful'
        ], 200);
    }

    /**
     * ✅ STATELESS Logout from all devices - revoke all tokens
     */
    public function logoutAll(Request $request): JsonResponse
    {
        // ✅ Delete all user tokens (stateless)
        $request->user()->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logged out from all devices successfully'
        ], 200);
    }

    /**
     * ✅ STATELESS Get current user info
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // ✅ Load fresh user data with roles (stateless)
        $user = $user->fresh(['roles']);

        return response()->json([
            'status' => true,
            'user' => new UserResource($user),
            'token_info' => [
                'expires_at' => $request->user()->currentAccessToken()->expires_at?->toISOString(),
                'last_used_at' => $request->user()->currentAccessToken()->last_used_at?->toISOString(),
                'created_at' => $request->user()->currentAccessToken()->created_at->toISOString()
            ]
        ], 200);
    }

    /**
     * ✅ STATELESS Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // ✅ Check current password
        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Current password is incorrect'
            ], 422);
        }

        // ✅ Update password
        $user->update([
            'password' => Hash::make($data['new_password'])
        ]);

        // ✅ Revoke all existing tokens (force re-login) - stateless security
        $user->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully. Please login again with your new password.'
        ], 200);
    }

    /**
     * ✅ STATELESS Get active sessions/tokens
     */
    public function activeSessions(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $tokens = $user->tokens()->select([
            'id',
            'name',
            'last_used_at',
            'created_at',
            'expires_at'
        ])->get()->map(function ($token) use ($request) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'last_used' => $token->last_used_at?->diffForHumans(),
                'created' => $token->created_at->diffForHumans(),
                'expires' => $token->expires_at?->diffForHumans(),
                'is_current' => $token->id === $request->user()->currentAccessToken()->id,
                'is_expired' => $token->expires_at && $token->expires_at->isPast()
            ];
        });

        return response()->json([
            'status' => true,
            'sessions' => $tokens,
            'total_active' => $tokens->where('is_expired', false)->count()
        ], 200);
    }

    /**
     * ✅ STATELESS Revoke specific token/session
     */
    public function revokeSession(Request $request, $tokenId): JsonResponse
    {
        $user = $request->user();
        
        $token = PersonalAccessToken::where('id', $tokenId)
                                   ->where('tokenable_id', $user->id)
                                   ->where('tokenable_type', get_class($user))
                                   ->first();

        if (!$token) {
            return response()->json([
                'status' => false,
                'message' => 'Session not found'
            ], 404);
        }

        // ✅ Don't allow revoking current token
        if ($token->id === $request->user()->currentAccessToken()->id) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot revoke current session. Use logout instead.'
            ], 422);
        }

        $token->delete();

        return response()->json([
            'status' => true,
            'message' => 'Session revoked successfully'
        ], 200);
    }

    /**
     * ✅ STATELESS Token validation endpoint
     */
    public function validateToken(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        
        return response()->json([
            'status' => true,
            'valid' => true,
            'token_info' => [
                'id' => $token->id,
                'name' => $token->name,
                'expires_at' => $token->expires_at?->toISOString(),
                'last_used_at' => $token->last_used_at?->toISOString(),
                'created_at' => $token->created_at->toISOString(),
                'is_expired' => $token->expires_at && $token->expires_at->isPast(),
                'expires_in_minutes' => $token->expires_at ? now()->diffInMinutes($token->expires_at, false) : null
            ],
            'user' => new UserResource($request->user()->load('roles'))
        ], 200);
    }

    /**
     * ✅ STATELESS Clean expired tokens (maintenance endpoint)
     */
    public function cleanExpiredTokens(Request $request): JsonResponse
    {
        // ✅ Only allow for current user or admin
        $user = $request->user();
        
        $deletedCount = PersonalAccessToken::where('tokenable_id', $user->id)
                                          ->where('tokenable_type', get_class($user))
                                          ->where('expires_at', '<', now())
                                          ->delete();

        return response()->json([
            'status' => true,
            'message' => 'Expired tokens cleaned successfully',
            'deleted_count' => $deletedCount
        ], 200);
    }
}

