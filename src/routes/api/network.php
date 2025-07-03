<?php

use App\Http\Requests\ApiStoreNetworkRequest;
use App\Http\Resources\NetworkCollection;
use App\Http\Resources\NetworkResource;
use App\Models\Client;
use App\Models\Network;
use Illuminate\Support\Facades\Route;

// Gets a collection of clients per network.
// GET /api/network/clients
Route::middleware('auth:sanctum')->get('/network/clients', function () {

    $clients = [];
    $networks = Network::all()->pluck('name');

    foreach ($networks as $network) {
        $client = Client::join('networks', 'networks.id', '=', 'clients.network_id')
            ->where('clients.enabled', true)
            ->where('networks.name', $network)
            ->first();

        $clients[$network] = $client->meta;
    }

    return $clients;
});

// GET /api/network
Route::middleware('auth:sanctum')->get('/network', function () {
    return new NetworkCollection(Network::all());
});

// POST /api/network
Route::middleware('auth:sanctum')->post('/network', function (ApiStoreNetworkRequest $request) {
    $validated = $request->validated();

    $network = Network::create([
        'name' => $validated['name'],
    ]);

    return redirect("/api/network/{$network->id}");
});

// GET /api/network/:id
Route::middleware('auth:sanctum')->get('/network/{id}', function (string $id) {
    return new NetworkResource(Network::findOrFail($id));
});

// PUT /api/network/:id
Route::middleware('auth:sanctum')->put('/network/{id}', function (string $id, ApiStoreNetworkRequest $request) {
    $network = Network::findOrFail($id);

    $validated = $request->validated();
    $network->name = $validated['name'];
    $network->save();

    return redirect("/api/network/$id");
});

// DEL /api/network/:id
Route::middleware('auth:sanctum')->delete('/network/{id}', function (string $id) {
    $network = Network::findOrFail($id);
    $name = $network->name;
    $network->delete();

    return response()->json([
        'success' => true,
        'message' => "Network: $name with id: $id was deleted.",
    ]);
});
