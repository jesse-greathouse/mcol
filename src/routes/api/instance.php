<?php

use Illuminate\Support\Facades\Route;

use App\Models\Instance,
    App\Http\Requests\ApiStoreInstanceRequest,
    App\Http\Resources\InstanceCollection,
    App\Http\Resources\InstanceResource;

// GET /api/instance
Route::middleware('auth:sanctum')->get('/instance', function () {
    return new InstanceCollection(Instance::all());
});

// POST /api/instance
Route::middleware('auth:sanctum')->post('/instance', function (ApiStoreInstanceRequest $request) {
    $validated = $request->validated();

    $inputs = [
        'log_uri'  => $validated['log_uri'],
        'client_id' => $validated['client'],
    ];

    if (isset($validated['enabled'])) {
        $inputs['enabled'] = $validated['enabled'];
    }

    if (isset($validated['status'])) {
        $inputs['status'] = strtoupper($validated['status']);
    }

    if (isset($validated['desired_status'])) {
        $inputs['desired_status'] = $validated['desired_status'];
    }

    if (isset($validated['pid'])) {
        $inputs['pid'] = $validated['pid'];
    }

    $instance = Instance::create($inputs);
 
    return redirect("/api/instance/{$instance->id}");
});

// GET /api/instance/:id
Route::middleware('auth:sanctum')->get('/instance/{id}', function (string $id) {
    return new InstanceResource(Instance::findOrFail($id));
});

// PUT /api/instance/:id
Route::middleware('auth:sanctum')->put('/instance/{id}', function (string $id, ApiStoreInstanceRequest $request) {
    $instance = Instance::findOrFail($id);

    $validated = $request->validated();
    $instance->log_uri = $validated['log_uri'];
    $instance->client_id = $validated['client'];

    if (isset($validated['enabled'])) {
        $instance->enabled = $validated['enabled'];
    }

    if (isset($validated['status'])) {
        $instance->status = strtoupper($validated['status']);
    }

    if (isset($validated['desired_status'])) {
        $instance->desired_status = $validated['desired_status'];
    }

    if (isset($validated['pid'])) {
        $instance->pid = $validated['pid'];
    }

    $instance->save();

    return redirect("/api/instance/$id");
});

// DEL /api/instance/:id
Route::middleware('auth:sanctum')->delete('/instance/{id}', function (string $id) {
    $instance = Instance::findOrFail($id);
    $log = $instance->log_uri;
    $instance->delete();

    return response()->json([
        'success' => true,
        'message' => "Instance: $log with id: $id was deleted."
    ]);
});
