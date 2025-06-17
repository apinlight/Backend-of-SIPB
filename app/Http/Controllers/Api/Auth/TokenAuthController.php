<?php
namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenAuthController extends Controller
{
    /**
     * Token-based login (stateless)
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        // ✅ Load roles relationship
        $user = User::with('roles')->where($loginType, $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Delete old tokens (optional - for single session)
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth-token', ['*'], now()->addHours(4))->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'user' => new UserResource($user), // ✅ Now includes roles
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 14400 // 4 hours in seconds
        ], 200);
    }

    /**
     * Token-based register
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'branch_name' => 'required|string|max:255',
        ]);

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'branch_name' => $request->branch_name,
        ]);

        // Assign default role and load relationships
        $user->assignRole('user');
        $user->load('roles'); // ✅ Load roles after assignment

        $token = $user->createToken('auth-token', ['*'], now()->addHours(4))->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Registration successful',
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 14400
        ], 201);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles'); // ✅ Load roles
        
        // Delete current token
        $request->user()->currentAccessToken()->delete();
        
        // Create new token
        $token = $user->createToken('auth-token', ['*'], now()->addHours(4))->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Token refreshed successfully',
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 14400
        ], 200);
    }
}
