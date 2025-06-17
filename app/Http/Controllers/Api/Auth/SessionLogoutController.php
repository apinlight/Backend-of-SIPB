<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SessionLogoutController extends Controller
{
    public function destroy(Request $request): JsonResponse
    {
        try {
            Log::info('=== LOGOUT START ===', [
                'session_id' => session()->getId(),
                'auth_check_web' => Auth::guard('web')->check(),
                'auth_check_sanctum' => Auth::guard('sanctum')->check(),
                'user_web' => Auth::guard('web')->user()?->username,
                'user_sanctum' => Auth::guard('sanctum')->user()?->username,
            ]);

            // 🔥 CRITICAL: For stateful auth, use web guard
            if (Auth::guard('web')->check()) {
                $user = Auth::guard('web')->user();
                $userId = $user->getAuthIdentifier();
                
                Log::info('Processing stateful logout for user: ' . $user->username);

                // Delete all sessions for this user
                $deletedSessions = DB::table('sessions')
                    ->where('user_id', $userId)
                    ->delete();

                Log::info('Deleted sessions count: ' . $deletedSessions);

                // Logout from web guard
                Auth::guard('web')->logout();
                
                // Invalidate current session
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                Log::info('Stateful logout completed');

                return response()->json([
                    'status' => true,
                    'message' => 'Logout successful',
                    'type' => 'stateful'
                ], 200);
            }

            // Handle token-based logout if needed
            if (Auth::guard('sanctum')->check()) {
                $user = Auth::guard('sanctum')->user();
                
                // If it's a token, revoke it
                if ($request->user()->currentAccessToken()) {
                    $request->user()->currentAccessToken()->delete();
                }
                
                Log::info('Token logout completed for user: ' . $user->username);

                return response()->json([
                    'status' => true,
                    'message' => 'Token revoked successfully',
                    'type' => 'token'
                ], 200);
            }

            // No authenticated user found
            Log::warning('Logout attempted but no authenticated user found');
            
            return response()->json([
                'status' => true,
                'message' => 'Already logged out',
                'type' => 'none'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => false,
                'message' => 'Logout failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}