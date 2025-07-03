<?php

use App\Http\Requests\ApiStoreClientRequest;
use App\Http\Resources\ClientCollection;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Support\Facades\Route;

// GET /api/client
Route::middleware('auth:sanctum')->get('/client', function () {
    return new ClientCollection(Client::all());
});

// POST /api/client
Route::middleware('auth:sanctum')->post('/client', function (ApiStoreClientRequest $request) {
    $validated = $request->validated();

    $inputs = [
        'nick' => $validated['nick'],
        'network_id' => $validated['network'],
    ];

    if (isset($validated['enabled'])) {
        $inputs['enabled'] = $validated['enabled'];
    }

    $client = Client::create($inputs);

    return redirect("/api/client/{$client->id}");
});

// GET /api/client/:id
Route::middleware('auth:sanctum')->get('/client/{id}', function (string $id) {
    return new ClientResource(Client::findOrFail($id));
});

// PUT /api/client/:id
Route::middleware('auth:sanctum')->put('/client/{id}', function (string $id, ApiStoreClientRequest $request) {
    $client = Client::findOrFail($id);

    $validated = $request->validated();
    $client->nick = $validated['nick'];
    $client->network_id = $validated['network'];

    if (isset($validated['enabled'])) {
        $client->enabled = $validated['enabled'];
    }

    $client->save();

    return redirect("/api/client/$id");
});

// DEL /api/client/:id
Route::middleware('auth:sanctum')->delete('/client/{id}', function (string $id) {
    $client = Client::findOrFail($id);
    $nick = $client->nick->nick;
    $network = $client->network->name;
    $client->delete();

    return response()->json([
        'success' => true,
        'message' => "Client: $nick@$network with id: $id was deleted.",
    ]);
});
