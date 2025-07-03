<?php

use App\Chat\Log\Streamer;
use App\Models\Channel;
use App\Models\Network;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// GET /stream/network/:name/channel/:channel/event
Route::middleware('auth:sanctum')->get('/network/{name}/channel/{channelName}/event', function (string $name, string $channelName, Request $request, Streamer $streamer) {
    $network = Network::where('name', $name)->first();

    if ($network === null) {
        throw new HttpResponseException(response("Invalid Network: $name", 400));
    }

    $channel = Channel::where('name', "#$channelName")->first();

    if ($channel === null) {
        throw new HttpResponseException(response("Invalid Channel: $name", 400));
    }

    $offset = ($request->has('offset')) ? (int) $request->input('offset') : 0;

    return response()->stream(function () use ($streamer, $name, $offset, $channelName): void {
        foreach ($streamer->event($name, "#$channelName", $offset) as $line) {
            echo $line;
            ob_flush();
            flush();
        }
    }, 200, [
        'X-Accel-Buffering' => 'no',
        'Cache-Control' => 'no-cache',
    ]);
});
