<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceHttpsInProduction
{
    public function handle(Request $request, Closure $next)
    {
        // ✅ Force HTTPS context when behind Cloudflare
        if ($request->header('CF-Visitor')) {
            $request->server->set('HTTPS', 'on');
            $request->server->set('SERVER_PORT', 443);
            $request->server->set('REQUEST_SCHEME', 'https');
        }

        // ✅ Trust X-Forwarded-Proto from Cloudflare
        if ($request->header('X-Forwarded-Proto') === 'https') {
            $request->server->set('HTTPS', 'on');
        }

        return $next($request);
    }
}
