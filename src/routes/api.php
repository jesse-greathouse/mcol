<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ... other routes
require __DIR__ . '/api/bot.php';
require __DIR__ . '/api/channel.php';
require __DIR__ . '/api/client.php';
require __DIR__ . '/api/download.php';
require __DIR__ . '/api/file-extension.php';
require __DIR__ . '/api/file-first-appearance.php';
require __DIR__ . '/api/network.php';
require __DIR__ . '/api/nick.php';
require __DIR__ . '/api/packet.php';
