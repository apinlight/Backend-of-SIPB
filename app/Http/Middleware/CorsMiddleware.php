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
            'http://127.0.0.2:5173', // ✅ FIX: Standard Vite port
            'http://localhost:5173',
            'http://127.0.0.1:80',
            'http://localhost:3000', // ✅ ADD: React dev server
        ];

        // ✅ FIX: Proper CORS logic
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        } else {
            // ✅ FIX: For non-allowed origins, don't set credentials
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Credentials', 'false');
        }

        // ✅ FIX: Don't set credentials twice - removed duplicate line
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $response->headers->set('Access-Control-Allow-Headers', 
            'Accept, Authorization, Content-Type, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Origin, Cache-Control, Pragma'
        );
        $response->headers->set('Access-Control-Expose-Headers', 'Set-Cookie');
        $response->headers->set('Access-Control-Max-Age', '86400');

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
