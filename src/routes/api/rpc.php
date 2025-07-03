<?php

use App\Http\Procedures\CancelProcedure;
use App\Http\Procedures\DownloadProcedure;
use App\Http\Procedures\RemoveCompletedProcedure;
use App\Http\Procedures\RemoveProcedure;
use Illuminate\Support\Facades\Route;

// POST /api/rpc/download
Route::rpc('/rpc/download', [DownloadProcedure::class])
    ->name('rpc.download')
    ->middleware([
        'auth:sanctum', config('jetstream.auth_session'), 'verified',
    ]);

// POST /api/rpc/remove
Route::rpc('/rpc/remove', [RemoveProcedure::class])
    ->name('rpc.remove')
    ->middleware([
        'auth:sanctum', config('jetstream.auth_session'), 'verified',
    ]);

// POST /api/rpc/cancel
Route::rpc('/rpc/cancel', [CancelProcedure::class])
    ->name('rpc.cancel')
    ->middleware([
        'auth:sanctum', config('jetstream.auth_session'), 'verified',
    ]);

// POST /api/rpc/remove-completed
Route::rpc('/rpc/remove-completed', [RemoveCompletedProcedure::class])
    ->name('rpc.removeCompleted')
    ->middleware([
        'auth:sanctum', config('jetstream.auth_session'), 'verified',
    ]);
