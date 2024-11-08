<?php

use Illuminate\Http\Request,
    Illuminate\Support\Facades\Route,
    Illuminate\Http\Exceptions\HttpResponseException;

use App\Chat\Log\Streamer,
    App\Models\Network;

// GET /stream/network/:name/privmsg
Route::middleware('auth:sanctum')->get('/network/{name}/privmsg', function (string $name, Request $request, Streamer $streamer) {
    $network = Network::where('name', $name)->first();

    if (null === $network) {
        throw new HttpResponseException(response("Invalid Network: $name", 400));
    }

    $offset = ($request->has('offset')) ? (int) $request->input('offset') : 0;

    return response()->stream(function () use ($streamer, $name, $offset): void {
        foreach ($streamer->privmsg($name, $offset) as [$line, $length]) {
            $offset += $length;
            echo $line;
            ob_flush();
            flush();
        }

        // meta data information about result.
        echo '[meta]: ' . json_encode(['offset' => $offset]);
    }, 200, [
        'X-Accel-Buffering' => 'no',
        'Cache-Control' => 'no-cache',
    ]);
});
