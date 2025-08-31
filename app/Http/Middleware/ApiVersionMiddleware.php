<?php
// app/Http/Middleware/ApiVersionMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiVersionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Set API version from header or default to v1
        $version = $request->header('Api-Version', 'v1');
        
        // Store version in request for controllers to use
        $request->attributes->set('api_version', $version);
        
        // Add version to response headers
        $response = $next($request);
        $response->headers->set('Api-Version', $version);
        
        return $response;
    }
}
