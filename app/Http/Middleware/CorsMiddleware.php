<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Log request details for debugging
        Log::info('CORS Middleware - Request', [
            'method' => $request->method(),
            'origin' => $request->header('Origin'),
            'path' => $request->path(),
            'cf_connecting_ip' => $request->header('CF-Connecting-IP'),
        ]);

        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 200);
        } else {
            $response = $next($request);
        }

        // Get origin
        $origin = $request->header('Origin');
        $allowedOrigins = [
            'https://fe-sipb.crulxproject.com',
            'https://sipb.crulxproject.com',
            'http://127.0.0.2:5173',
            'http://127.0.0.1:80',
        ];

        // Set CORS headers
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        
        } else {
            // Don't set credentials header for non-allowed origins
            $response->headers->set('Access-Control-Allow-Credentials', 'false');
        }




        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Accept, Authorization, Content-Type, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Origin, Cache-Control, Pragma');
        $response->headers->set('Access-Control-Expose-Headers', 'Set-Cookie');
        $response->headers->set('Access-Control-Max-Age', '86400');

        Log::info('CORS Middleware - Response', [
            'status' => $response->getStatusCode(),
            'origin_header' => $response->headers->get('Access-Control-Allow-Origin'),
            'credentials' => $response->headers->get('Access-Control-Allow-Credentials'),
        ]);

        return $response;
    }
}
