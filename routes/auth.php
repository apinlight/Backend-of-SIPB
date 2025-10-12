<?php

use App\Http\Controllers\Api\Auth\TokenAuthController;
use Illuminate\Support\Facades\Route;

// ✅ PUBLIC AUTH ROUTES - No authentication required (STATELESS)
Route::prefix('api/v1')
    ->middleware(['api.public', 'api.rate_limit:5,1']) // ✅ 5 attempts per minute for auth
    ->group(function () {

        // ✅ Guest routes (no auth required)
        Route::post('/register', [TokenAuthController::class, 'register'])
            ->name('register');

        Route::post('/login', [TokenAuthController::class, 'login'])
            ->name('login');
    });

// ✅ PROTECTED AUTH ROUTES - Authentication required (STATELESS)
Route::prefix('api/v1')
    ->middleware(['api.protected', 'api.rate_limit:30,1']) // ✅ 30 requests per minute for authenticated
    ->group(function () {

        // ✅ Token management
        Route::post('/refresh-token', [TokenAuthController::class, 'refresh'])
            ->name('token.refresh');

        Route::post('/logout', [TokenAuthController::class, 'logout'])
            ->name('logout');

        Route::post('/logout-all', [TokenAuthController::class, 'logoutAll'])
            ->name('logout.all');

        // ✅ User info and profile
        Route::get('/me', [TokenAuthController::class, 'me'])
            ->name('auth.me');

        Route::post('/validate-token', [TokenAuthController::class, 'validateToken'])
            ->name('token.validate');

        // ✅ Password management (more restrictive)
        Route::post('/change-password', [TokenAuthController::class, 'changePassword'])
            ->middleware('api.rate_limit:3,5') // ✅ Only 3 password changes per 5 minutes
            ->name('password.change');

        // ✅ Session management (stateless tokens)
        Route::get('/sessions', [TokenAuthController::class, 'activeSessions'])
            ->name('auth.sessions');

        Route::delete('/sessions/{tokenId}', [TokenAuthController::class, 'revokeSession'])
            ->name('auth.revoke-session');

        Route::delete('/sessions/expired', [TokenAuthController::class, 'cleanExpiredTokens'])
            ->name('auth.clean-expired');
    });
