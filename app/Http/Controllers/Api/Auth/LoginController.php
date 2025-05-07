<?php
namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('login', 'password');

        if (!Auth::attempt(['username' => $credentials['login'], 'password' => $credentials['password']])) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }   

        $user = Auth::user();
        $token = $user->createToken('api-login')->plainTextToken;
        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => new UserResource($user)
        ], 200);

    }
    
}