<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\BrowseController,
    App\Http\Controllers\DashboardController,
    App\Http\Controllers\DownloadQueueController,
    App\Http\Controllers\TokenController;

// Force all traffic to authenticate first.
Route::redirect('/', 'login');

// Redirect Home to dashboard.
Route::redirect('/home', 'dashboard');

// Token handling
Route::post('/tokens/create', [TokenController::class, 'create']);
Route::get('/tokens/create/{token_name}', [TokenController::class, 'renwew']);

Route::middleware([
    'auth:sanctum', config('jetstream.auth_session'), 'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/browse', [BrowseController::class, 'index'])->name('browse');
    Route::get('/download-queue', [DownloadQueueController::class, 'index'])->name('download-queue');
});
