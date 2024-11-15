<?php

use Illuminate\Http\Request,
    Illuminate\Support\Facades\Route,
    Illuminate\Http\Exceptions\HttpResponseException;

use App\Chat\Log\Streamer,
    App\Models\Network;

// GET /stream/network/:name/console
Route::middleware('auth:sanctum')->get('/network/{name}/console', function (string $name, Request $request, Streamer $streamer) {
    $network = Network::where('name', $name)->first();

    if (null === $network) {
        throw new HttpResponseException(response("Invalid Network: $name", 400));
    }

    $offset = ($request->has('offset')) ? (int) $request->input('offset') : 0;

    return response()->stream(function () use ($streamer, $name, $offset): void {
        foreach ($streamer->console($name, $offset) as $line) {
            echo $line;
            ob_flush();
            flush();
        }
    }, 200, [
        'X-Accel-Buffering' => 'no',
        'Cache-Control' => 'no-cache',
    ]);
});
