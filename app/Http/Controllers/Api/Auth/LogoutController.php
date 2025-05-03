<?php
namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LogoutController extends Controller
{
    public function destroy(): JsonResponse
    {
        auth()->user()?->currentAccessToken()?->delete();

        return response()->json(null, 204);
    }
}