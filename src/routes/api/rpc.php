<?php

use Illuminate\Support\Facades\Route;

use App\Http\Procedures\DownloadProcedure;

// GET /api/rpc/download
Route::rpc('/rpc/download',  [DownloadProcedure::class])
    ->name('rpc.download')
    ->middleware([
        'auth:sanctum', config('jetstream.auth_session'), 'verified',
    ]);
