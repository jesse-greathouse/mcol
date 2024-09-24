<?php

use Illuminate\Support\Facades\Route;

use App\Models\PacketSearchResult,
    App\Http\Resources\PacketSearchResultCollection,
    App\Http\Resources\PacketSearchResultResource;

// GET /api/fileextension
Route::middleware('auth:sanctum')->get('/packet-search-result', function () {
    return new PacketSearchResultCollection(PacketSearchResult::paginate());
});

// GET /api/fileextension/:id
Route::middleware('auth:sanctum')->get('/packet-search-result/{id}', function (string $id) {
    return new PacketSearchResultResource(PacketSearchResult::findOrFail($id));
});
