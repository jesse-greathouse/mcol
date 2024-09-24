<?php

use Illuminate\Support\Facades\Route;

use App\Models\Channel,
    App\Http\Requests\ApiStoreChannelRequest,
    App\Http\Resources\ChannelCollection,
    App\Http\Resources\ChannelResource;

// GET /api/channel
Route::middleware('auth:sanctum')->get('/channel', function () {
    return new ChannelCollection(Channel::all());
});

// POST /api/channel
Route::middleware('auth:sanctum')->post('/channel', function (ApiStoreChannelRequest $request) {
    $validated = $request->validated();

    $inputs = [
        'name' => $validated['name'],
        'network_id' => $validated['network'],
        'topic' => $validated['topic'],
        'users' => 0,
    ];

    if (isset($validated['parent'])) {
        $inputs['channel_id'] = $validated['parent'];
    }

    if (isset($validated['users'])) {
        $inputs['users'] = $validated['users'];
    }

    $channel = Channel::create($inputs);
 
    return redirect("/api/channel/{$channel->id}");
});

// GET /api/channel/:id
Route::middleware('auth:sanctum')->get('/channel/{id}', function (string $id) {
    return new ChannelResource(Channel::findOrFail($id));
});

// PUT /api/channel/:id
Route::middleware('auth:sanctum')->put('/channel/{id}', function (string $id, ApiStoreChannelRequest $request) {
    $channel = Channel::findOrFail($id);

    $validated = $request->validated();
    $channel->name = $validated['name'];
    $channel->network_id = $validated['network'];
    $channel->topic = $validated['topic'];

    if (isset($validated['parent'])) {
        $channel->channel_id = $validated['parent'];
    }

    if (isset($validated['users'])) {
        $channel->users = $validated['users'];
    }

    $channel->save();

    return redirect("/api/channel/$id");
});

// DEL /api/channel/:id
Route::middleware('auth:sanctum')->delete('/channel/{id}', function (string $id) {
    $channel = Channel::findOrFail($id);
    $name = $channel->name;
    $channel->delete();

    return response()->json([
        'success' => true,
        'message' => "Channel: $name with id: $id was deleted."
    ]);
});
