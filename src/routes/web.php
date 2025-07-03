<?php

use App\Http\Controllers\BrowseController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\DownloadProgressController;
use App\Http\Controllers\DownloadQueueController;
use App\Http\Controllers\TokenController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::get('/download', [DownloadController::class, 'index'])->name('download');
    Route::get('/download-queue', [DownloadQueueController::class, 'index'])->name('download-queue');
    Route::get('/download-progress', [DownloadProgressController::class, 'show']);
    Route::get('/download.svg', [DownloadProgressController::class, 'show']);
});
