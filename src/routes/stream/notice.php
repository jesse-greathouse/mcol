<?php

use App\Chat\Log\Streamer;
use App\Models\Network;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// GET /stream/network/:name/notice
Route::middleware('auth:sanctum')->get('/network/{name}/notice', function (string $name, Request $request, Streamer $streamer) {
    $network = Network::where('name', $name)->first();

    if ($network === null) {
        throw new HttpResponseException(response("Invalid Network: $name", 400));
    }

    $offset = ($request->has('offset')) ? (int) $request->input('offset') : 0;

    return response()->stream(function () use ($streamer, $name, $offset): void {
        foreach ($streamer->notice($name, $offset) as $line) {
            echo $line;
            ob_flush();
            flush();
        }
    }, 200, [
        'X-Accel-Buffering' => 'no',
        'Cache-Control' => 'no-cache',
    ]);
});
