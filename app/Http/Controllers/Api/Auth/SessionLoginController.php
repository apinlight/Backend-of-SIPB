<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SessionLoginController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        Log::info('=== LOGIN ATTEMPT START ===', [
            'login' => $request->input('login'),
            'session_id_before' => session()->getId(),
            'has_session' => session()->isStarted(),
            'user_agent' => $request->userAgent()
        ]);

        $request->authenticate();
        
        // ✅ Load roles when getting user
        $user = Auth::user()->load('roles');

        // Clear other sessions for this user
        DB::table('sessions')
            ->where('user_id', $user->getAuthIdentifier())
            ->where('id', '!=', session()->getId())
            ->delete();

        $request->session()->regenerate();

        Log::info('Login successful', [
            'user' => $user->username,
            'session_id_after' => session()->getId(),
            'auth_check' => Auth::check()
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'user' => new UserResource($user),
            'debug' => [
                'session_id' => session()->getId(),
                'auth_check' => Auth::check(),
                'guard' => Auth::getDefaultDriver()
            ]
        ], 200);
    }
}
