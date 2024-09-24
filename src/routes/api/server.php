<?php

use Illuminate\Support\Facades\Route;

use App\Models\Server,
    App\Http\Requests\ApiStoreServerRequest,
    App\Http\Resources\ServerCollection,
    App\Http\Resources\ServerResource;

// GET /api/server
Route::middleware('auth:sanctum')->get('/server', function () {
    return new ServerCollection(Server::paginate());
});

// POST /api/server
Route::middleware('auth:sanctum')->post('/server', function (ApiStoreServerRequest $request) {
    $validated = $request->validated();

    $server = Server::create([
        'host' => $validated['host'],
        'network_id' => $validated['network'],
    ]);
 
    return redirect("/api/server/{$server->id}");
});

// GET /api/server/:id
Route::middleware('auth:sanctum')->get('/server/{id}', function (string $id) {
    return new ServerResource(Server::findOrFail($id));
});

// PUT /api/server/:id
Route::middleware('auth:sanctum')->put('/server/{id}', function (string $id, ApiStoreServerRequest $request) {
    $server = Server::findOrFail($id);

    $validated = $request->validated();
    $server->host = $validated['host'];
    $server->network_id = $validated['network'];

    $server->save();

    return redirect("/api/server/$id");
});

// DEL /api/server/:id
Route::middleware('auth:sanctum')->delete('/server/{id}', function (string $id) {
    $server = Server::findOrFail($id);
    $host = $server->host;
    $server->delete();

    return response()->json([
        'success' => true,
        'message' => "Server: $host with id: $id was deleted."
    ]);
});
