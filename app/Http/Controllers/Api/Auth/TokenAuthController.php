<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class TokenAuthController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        // Rate limiting is now handled by middleware.
        $tokenData = $this->authService->login($request->validated(), $request->ip());

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'user' => UserResource::make($tokenData['user']),
            'token' => $tokenData['token'],
            'token_type' => $tokenData['token_type'],
            'expires_in' => $tokenData['expires_in'],
            'expires_at' => $tokenData['expires_at'],
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        // Rate limiting is handled by middleware, authorization by the Form Request.
        $tokenData = $this->authService->register($request->validated());
        
        return response()->json([
            'status' => true,
            'message' => 'Registration successful',
            'user' => UserResource::make($tokenData['user']),
            'token' => $tokenData['token'],
            'token_type' => $tokenData['token_type'],
            'expires_in' => $tokenData['expires_in'],
            'expires_at' => $tokenData['expires_at'],
        ], 201);
    }

    public function refresh(Request $request): JsonResponse
    {
        $tokenData = $this->authService->refreshToken($request->user());

        return response()->json([
            'status' => true,
            'message' => 'Token refreshed successfully',
            'user' => UserResource::make($tokenData['user']),
            'token' => $tokenData['token'],
            'token_type' => $tokenData['token_type'],
            'expires_in' => $tokenData['expires_in'],
            'expires_at' => $tokenData['expires_at'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['status' => true, 'message' => 'Logout successful']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        return response()->json(['status' => true, 'message' => 'Logged out from all devices successfully']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'user' => UserResource::make($request->user()->fresh('roles'))]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->authService->changePassword($request->user(), $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully. Please login again with your new password.'
        ]);
    }

    public function activeSessions(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens;
        return response()->json(['status' => true, 'sessions' => $tokens]);
    }

    public function revokeSession(Request $request, $tokenId): JsonResponse
    {
        $token = $request->user()->tokens()->where('id', $tokenId)->firstOrFail();
        if ($token->id === $request->user()->currentAccessToken()->id) {
            return response()->json(['status' => false, 'message' => 'Cannot revoke current session.'], 422);
        }
        $token->delete();
        return response()->json(['status' => true, 'message' => 'Session revoked successfully']);
    }
}