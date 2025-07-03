<?php

use App\Http\Requests\StreamSystemMessageRequest;
use App\SystemMessage;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Route;

// GET /stream/message
Route::middleware('auth:sanctum')->get('/system-message', function (StreamSystemMessageRequest $request, SystemMessage $systemMessage) {
    return response()->stream(function () use ($request, $systemMessage): void {
        $ts = date('c'); // ISO 8601 date	2004-02-12T15:19:21+00:00

        try {
            // queue is the short-form, e.g.: "chat"
            // The system.message exchange will be prepended to the queue: system.message.chat
            $queue = $request->input('queue');
            $routingKey = $request->input('routing_key') ?? '';

            // Will stream the buffer for every line
            // Formats the line like: [2025-03-14T20:09:28+00:00][system.message.chat.Network.notice] ** Welcome Back!'
            foreach ($systemMessage->fetch($queue, $routingKey) as $msg) {
                $txt = json_decode($msg->getBody(), true, 512, JSON_THROW_ON_ERROR);
                printf("%s:::%s:::%s\n", $ts, $msg->getRoutingKey(), $txt);
                ob_flush();
                flush();
            }
        } catch (Throwable $e) {
            throw new HttpResponseException(response()->make(sprintf("%s:::%s:::%s\n", $ts, 'error', $e->getMessage()), 500));
        }
    }, 200, [
        'X-Accel-Buffering' => 'no',
        'Cache-Control' => 'no-cache',
    ]);
});
