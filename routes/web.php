<?php
// routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocsController;

// ✅ Add security headers to web routes
Route::middleware(['web'])->group(function () {
    
    Route::get('/', function() {
        return view('welcome');
    });

    Route::get('/docs/{doc?}', [DocsController::class, 'index'])
        ->where('doc', 'readme|api')
        ->name('docs.index');
});

// ✅ Include auth routes
require __DIR__ . '/auth.php';
