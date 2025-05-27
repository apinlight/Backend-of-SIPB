<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocsController;

Route::get('/', function() {
    return view('welcome');
});
Route::get('/docs/{doc?}', [DocsController::class, 'index'])
    ->where('doc', 'readme|api')
    ->name('docs.index');

require __DIR__ . '/auth.php';
