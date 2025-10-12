<?php

// app/Http/Middleware/RequestValidationMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestValidationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Validate common request requirements
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {

            // Check content type for JSON APIs
            if (str_starts_with($request->path(), 'api/')) {
                $contentType = $request->header('Content-Type');

                if (! str_contains($contentType, 'application/json') &&
                    ! str_contains($contentType, 'multipart/form-data')) {

                    return response()->json([
                        'status' => false,
                        'message' => 'Content-Type must be application/json or multipart/form-data',
                    ], 400);
                }
            }
        }

        // Validate request size (prevent large payloads)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($request->server('CONTENT_LENGTH') > $maxSize) {
            return response()->json([
                'status' => false,
                'message' => 'Request payload too large',
            ], 413);
        }

        return $next($request);
    }
}
