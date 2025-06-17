<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('=== REQUEST DEBUG ===', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'origin' => $request->header('Origin'),
            'user_agent' => $request->header('User-Agent'),
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept'),
            'x_forwarded_for' => $request->header('X-Forwarded-For'),
            'x_real_ip' => $request->header('X-Real-IP'),
            'cf_connecting_ip' => $request->header('CF-Connecting-IP'),
            'body' => $request->all(),
        ]);

        $response = $next($request);

        Log::info('=== RESPONSE DEBUG ===', [
            'status' => $response->getStatusCode(),
            'location' => $response->headers->get('Location'),
            'content_type' => $response->headers->get('Content-Type'),
            'cors_origin' => $response->headers->get('Access-Control-Allow-Origin'),
            'content_preview' => substr($response->getContent(), 0, 500),
        ]);

        return $response;
    }
}
