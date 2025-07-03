<?php

use App\Http\Resources\PacketSearchCollection;
use App\Http\Resources\PacketSearchResource;
use App\Models\PacketSearch;
use Illuminate\Support\Facades\Route;

// GET /api/fileextension
Route::middleware('auth:sanctum')->get('/packet-search', function () {
    return new PacketSearchCollection(PacketSearch::paginate());
});

// GET /api/fileextension/:id
Route::middleware('auth:sanctum')->get('/packet-search/{id}', function (string $id) {
    return new PacketSearchResource(PacketSearch::findOrFail($id));
});
