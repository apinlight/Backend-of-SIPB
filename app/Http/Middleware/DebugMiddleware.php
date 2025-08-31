<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // ✅ FIX: Only run in development environment
        if (!config('app.debug')) {
            return $next($request);
        }

        // ✅ FIX: Filter sensitive data
        $requestData = $this->filterSensitiveData($request->all());

        Log::info('=== REQUEST DEBUG ===', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'origin' => $request->header('Origin'),
            'user_agent' => substr($request->header('User-Agent') ?? '', 0, 100), // ✅ Truncate with null check
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept'),
            'x_forwarded_for' => $request->header('X-Forwarded-For'),
            'x_real_ip' => $request->header('X-Real-IP'),
            'cf_connecting_ip' => $request->header('CF-Connecting-IP'),
            'body' => $requestData, // ✅ Filtered data
            'authenticated' => Auth::check(), // ✅ FIXED: Use Auth facade
            'user_id' => Auth::id(), // ✅ FIXED: Use Auth facade
        ]);

        $response = $next($request);

        Log::info('=== RESPONSE DEBUG ===', [
            'status' => $response->getStatusCode(),
            'location' => $response->headers->get('Location'),
            'content_type' => $response->headers->get('Content-Type'),
            'cors_origin' => $response->headers->get('Access-Control-Allow-Origin'),
            'content_preview' => substr($response->getContent() ?? '', 0, 200), // ✅ Shorter preview with null check
        ]);

        return $response;
    }

    // ✅ ADD: Filter sensitive data
    private function filterSensitiveData(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'secret', 'key'];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($data[$key])) {
                $data[$key] = '[FILTERED]';
            }
        }

        return $data;
    }
}
