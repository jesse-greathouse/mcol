<?php

use Illuminate\Support\Facades\Route;

use App\Models\Bot,
    App\Http\Requests\ApiStoreBotRequest,
    App\Http\Resources\BotCollection,
    App\Http\Resources\BotResource;

// GET /api/bot
Route::middleware('auth:sanctum')->get('/bot', function () {
    return new BotCollection(Bot::all());
});

// POST /api/bot
Route::middleware('auth:sanctum')->post('/bot', function (ApiStoreBotRequest $request) {
    $validated = $request->validated();

    $bot = Bot::create([
        'nick' => $validated['nick'],
        'network_id' => $validated['network'],
    ]);
 
    return redirect("/api/bot/{$bot->id}");
});

// GET /api/bot/:id
Route::middleware('auth:sanctum')->get('/bot/{id}', function (string $id) {
    return new BotResource(Bot::findOrFail($id));
});

// PUT /api/bot/:id
Route::middleware('auth:sanctum')->put('/bot/{id}', function (string $id, ApiStoreBotRequest $request) {
    $bot = Bot::findOrFail($id);

    $validated = $request->validated();
    $bot->nick = $validated['nick'];
    $bot->network_id = $validated['network'];

    $bot->save();

    return redirect("/api/bot/$id");
});

// DEL /api/bot/:id
Route::middleware('auth:sanctum')->delete('/bot/{id}', function (string $id) {
    $bot = Bot::findOrFail($id);
    $nick = $bot->nick;
    $bot->delete();

    return response()->json([
        'success' => true,
        'message' => "Bot: $nick with id: $id was deleted."
    ]);
});
