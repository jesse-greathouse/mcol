<?php

use Illuminate\Support\Facades\Route;

use App\Models\Packet,
    App\Http\Requests\ApiStorePacketRequest,
    App\Http\Resources\PacketCollection,
    App\Http\Resources\PacketResource;

// GET /api/packet
Route::middleware('auth:sanctum')->get('/packet', function () {
    return new PacketCollection(Packet::paginate());
});

// POST /api/packet
Route::middleware('auth:sanctum')->post('/packet', function (ApiStorePacketRequest $request) {
    $validated = $request->validated();

    $inputs = [
        'number'        => $validated['number'],
        'size'          => $validated['size'],
        'file_name'     => $validated['file_name'],
        'bot_id'        => $validated['bot'],
        'channel_id'    => $validated['channel'],
        'network_id'    => $validated['network'],
    ];

    if (isset($validated['gets'])) {
        $inputs['gets'] = $validated['gets'];
    }

    $packet = Packet::create($inputs);
 
    return redirect("/api/packet/{$packet->id}");
});

// GET /api/packet/:id
Route::middleware('auth:sanctum')->get('/packet/{id}', function (string $id) {
    return new PacketResource(Packet::findOrFail($id));
});

// PUT /api/packet/:id
Route::middleware('auth:sanctum')->put('/packet/{id}', function (string $id, ApiStorePacketRequest $request) {
    $packet = Packet::findOrFail($id);

    $validated = $request->validated();
    $packet->number = $validated['number'];
    $packet->size = $validated['size'];
    $packet->file_name = $validated['file_name'];
    $packet->bot_id = $validated['bot'];
    $packet->channel_id = $validated['channel'];
    $packet->network_id = $validated['network'];

    if (isset($validated['gets'])) {
        $inputs['gets'] = $validated['gets'];
    }

    $packet->save();

    return redirect("/api/packet/$id");
});

// DEL /api/packet/:id
Route::middleware('auth:sanctum')->delete('/packet/{id}', function (string $id) {
    $packet = Packet::findOrFail($id);
    $name = $packet->file_name;
    $packet->delete();

    return response()->json([
        'success' => true,
        'message' => "Packet: $name with id: $id was deleted."
    ]);
});
