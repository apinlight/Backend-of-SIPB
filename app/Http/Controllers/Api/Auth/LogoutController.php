<?php
namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutController extends Controller
{
    public function destroy(Request $request): JsonResponse
    {
        try {
            $header = $request->bearerToken();
    
            if (!$header) {
                return response()->json(['message' => 'No token provided'], 401);
            }
    
            $token = PersonalAccessToken::findToken($header);
    
            if (!$token) {
                return response()->json(['message' => 'Invalid token'], 401);
            }
    
            $token->delete();
    
            return response()->json(['message' => 'Logout success'], 204);
        } catch (\Throwable $e) {
            \Log::error('Logout error: ' . $e->getMessage());
            return response()->json(['message' => 'Server error'], 500);
        }
    }



}