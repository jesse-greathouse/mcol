<?php

use Illuminate\Support\Facades\Route;

use App\Models\PacketSearch,
    App\Http\Resources\PacketSearchCollection,
    App\Http\Resources\PacketSearchResource;

// GET /api/fileextension
Route::middleware('auth:sanctum')->get('/packet-search', function () {
    return new PacketSearchCollection(PacketSearch::paginate());
});

// GET /api/fileextension/:id
Route::middleware('auth:sanctum')->get('/packet-search/{id}', function (string $id) {
    return new PacketSearchResource(PacketSearch::findOrFail($id));
});
