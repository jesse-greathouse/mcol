<?php

use Illuminate\Support\Facades\Route;

use App\Models\Operation,
    App\Http\Requests\ApiStoreOperationRequest,
    App\Http\Resources\OperationCollection,
    App\Http\Resources\OperationResource;

// GET /api/operation
Route::middleware('auth:sanctum')->get('/operation', function () {
    return new OperationCollection(Operation::paginate());
});

// POST /api/operation
Route::middleware('auth:sanctum')->post('/operation', function (ApiStoreOperationRequest $request) {
    $validated = $request->validated();

    $inputs = [
        'command'       => $validated['command'],
        'instance_id'   => $validated['instance'],
    ];

    if (isset($validated['enabled'])) {
        $inputs['enabled'] = $validated['enabled'];
    }

    if (isset($validated['status'])) {
        $inputs['status'] = strtoupper($validated['status']);
    }

    $operation = Operation::create($inputs);
 
    return redirect("/api/operation/{$operation->id}");
});

// GET /api/operation/:id
Route::middleware('auth:sanctum')->get('/operation/{id}', function (string $id) {
    return new OperationResource(Operation::findOrFail($id));
});

// PUT /api/operation/:id
Route::middleware('auth:sanctum')->put('/operation/{id}', function (string $id, ApiStoreOperationRequest $request) {
    $operation = Operation::findOrFail($id);

    $validated = $request->validated();
    $operation->command = $validated['command'];
    $operation->instance_id = $validated['instance'];

    if (isset($validated['enabled'])) {
        $operation->enabled = $validated['enabled'];
    }

    if (isset($validated['status'])) {
        $operation->status = strtoupper($validated['status']);
    }

    $operation->save();

    return redirect("/api/operation/$id");
});

// DEL /api/operation/:id
Route::middleware('auth:sanctum')->delete('/operation/{id}', function (string $id) {
    $operation = Operation::findOrFail($id);
    $command = $operation->command;
    $operation->delete();

    return response()->json([
        'success' => true,
        'message' => "Operation: $command with id: $id was deleted."
    ]);
});
