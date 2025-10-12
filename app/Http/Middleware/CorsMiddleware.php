<?php

// app/Http/Middleware/CorsMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // ✅ FIX: Only log in development
        if (config('app.debug')) {
            Log::info('CORS Middleware - Request', [
                'method' => $request->method(),
                'origin' => $request->header('Origin'),
                'path' => $request->path(),
            ]);
        }

        // Get origin and allowed origins from config
        $origin = $request->header('Origin');
        $allowedOrigins = config('cors.allowed_origins', []);

        // Prepare CORS headers
        $isAllowed = in_array($origin, $allowedOrigins);
        $headers = [
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS, PATCH',
            'Access-Control-Allow-Headers' => 'Accept, Authorization, Content-Type, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Origin, Cache-Control, Pragma',
            'Access-Control-Expose-Headers' => 'Set-Cookie',
            'Access-Control-Max-Age' => '86400',
        ];

        // Set origin header - either specific origin if allowed, or * for any origin
        if ($isAllowed && $origin) {
            $headers['Access-Control-Allow-Origin'] = $origin;
            $headers['Access-Control-Allow-Credentials'] = 'true';
        } else {
            $headers['Access-Control-Allow-Origin'] = '*';
            // NEVER set credentials header for wildcard origin per CORS spec
        }

        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            return response('', 200, $headers);
        }

        // Continue with the request
        $response = $next($request);

        // Remove any existing CORS headers to avoid conflicts
        $response->headers->remove('Access-Control-Allow-Origin');
        $response->headers->remove('Access-Control-Allow-Credentials');
        $response->headers->remove('Access-Control-Allow-Methods');
        $response->headers->remove('Access-Control-Allow-Headers');
        $response->headers->remove('Access-Control-Expose-Headers');
        $response->headers->remove('Access-Control-Max-Age');

        // Apply our CORS headers to the response
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        // ✅ FIX: Only log in development
        if (config('app.debug')) {
            Log::info('CORS Middleware - Response', [
                'status' => $response->getStatusCode(),
                'origin_header' => $response->headers->get('Access-Control-Allow-Origin'),
                'credentials' => $response->headers->get('Access-Control-Allow-Credentials'),
            ]);
        }

        return $response;
    }
}
