<?php

use Illuminate\Support\Facades\Route;

use App\Http\Procedures\DownloadProcedure,
    App\Http\Procedures\RemoveProcedure,
    App\Http\Procedures\RemoveCompletedProcedure,
    App\Http\Procedures\CancelProcedure;

// POST /api/rpc/download
Route::rpc('/rpc/download',  [DownloadProcedure::class])
    ->name('rpc.download')
    ->middleware([
        'auth:sanctum', config('jetstream.auth_session'), 'verified',
    ]);

// POST /api/rpc/remove
Route::rpc('/rpc/remove',  [RemoveProcedure::class])
    ->name('rpc.remove')
    ->middleware([
        'auth:sanctum', config('jetstream.auth_session'), 'verified',
    ]);

// POST /api/rpc/cancel
Route::rpc('/rpc/cancel',  [CancelProcedure::class])
    ->name('rpc.cancel')
    ->middleware([
        'auth:sanctum', config('jetstream.auth_session'), 'verified',
    ]);

// POST /api/rpc/remove-completed
Route::rpc('/rpc/remove-completed',  [RemoveCompletedProcedure::class])
    ->name('rpc.removeCompleted')
    ->middleware([
        'auth:sanctum', config('jetstream.auth_session'), 'verified',
    ]);
