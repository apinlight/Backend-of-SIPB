<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $user = Auth::user();

        // Check if user has any of the required roles
        $hasRole = collect($roles)->some(function ($role) use ($user) {
            return $user->hasRole($role);
        });

        if (! $hasRole) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied. Required roles: '.implode(', ', $roles),
            ], 403);
        }

        return $next($request);
    }
}
