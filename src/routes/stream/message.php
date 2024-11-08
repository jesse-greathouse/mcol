<?php

use Illuminate\Http\Request,
    Illuminate\Support\Facades\Route,
    Illuminate\Http\Exceptions\HttpResponseException;

use App\Chat\Log\Streamer,
    App\Models\Channel,
    App\Models\Network;

// GET /stream/network/:name/channel/:channel/message
Route::middleware('auth:sanctum')->get('/network/{name}/channel/{channelName}/message', function (string $name, string $channelName, Request $request, Streamer $streamer) {
    $network = Network::where('name', $name)->first();

    if (null === $network) {
        throw new HttpResponseException(response("Invalid Network: $name", 400));
    }

    $channel = Channel::where('name', "#$channelName")->first();

    if (null === $channel) {
        throw new HttpResponseException(response("Invalid Channel: $name", 400));
    }

    $offset = ($request->has('offset')) ? (int) $request->input('offset') : 0;

    return response()->stream(function () use ($streamer, $name, $offset, $channelName): void {
        foreach ($streamer->message($name, "#$channelName", $offset) as [$line, $length]) {
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
