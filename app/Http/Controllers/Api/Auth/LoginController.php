<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        // menggunakan fungsi built-in yang juga support rate limiting (LoginRequest)
        $request->authenticate(); 
        $user = Auth::user();

        DB::table('sessions')
            ->where('user_id', $user->getAuthIdentifier())
            ->where('id', '!=', session()->getId())
            ->delete();

        $request->session()->regenerate();

        Log::info('Session ID: ' . session()->getId());
        Log::info('User after login: ' . json_encode(Auth::guard('web')->user()));

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'user' => new UserResource($user)
        ], 200);
    }
}