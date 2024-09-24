<?php

use Illuminate\Support\Facades\Route;

use App\Models\Nick,
    App\Http\Requests\ApiStoreNickRequest,
    App\Http\Resources\NickCollection,
    App\Http\Resources\NickResource;

// GET /api/nick
Route::middleware('auth:sanctum')->get('/nick', function () {
    return new NickCollection(Nick::all());
});

// POST /api/nick
Route::middleware('auth:sanctum')->post('/nick', function (ApiStoreNickRequest $request) {
    $validated = $request->validated();

    $inputs = [
        'nick' => $validated['nick'],
        'network_id' => $validated['network'],
        'email' => null,
        'password' => null,
    ];

    if (isset($validated['email'])) {
        $inputs['email'] = $validated['email'];
    }

    if (isset($validated['password'])) {
        $inputs['password'] = $validated['password'];
    }

    $nick = Nick::create($inputs);
 
    return redirect("/api/nick/{$nick->id}");
});

// GET /api/nick/:id
Route::middleware('auth:sanctum')->get('/nick/{id}', function (string $id) {
    return new NickResource(Nick::findOrFail($id));
});

// PUT /api/nick/:id
Route::middleware('auth:sanctum')->put('/nick/{id}', function (string $id, ApiStoreNickRequest $request) {
    $nick = Nick::findOrFail($id);

    $validated = $request->validated();
    $nick->nick = $validated['nick'];
    $nick->network_id = $validated['network'];

    if (isset($validated['email'])) {
        $nick->email = $validated['email'];
    }

    if (isset($validated['password'])) {
        $nick->password = $validated['password'];
    }

    $nick->save();

    return redirect("/api/nick/$id");
});

// DEL /api/nick/:id
Route::middleware('auth:sanctum')->delete('/nick/{id}', function (string $id) {
    $nick = Nick::findOrFail($id);
    $nickStr = $nick->nick;
    $nick->delete();

    return response()->json([
        'success' => true,
        'message' => "Nick: $nickStr with id: $id was deleted."
    ]);
});
