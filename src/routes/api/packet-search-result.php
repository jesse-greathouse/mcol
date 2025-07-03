<?php

use App\Http\Resources\PacketSearchResultCollection;
use App\Http\Resources\PacketSearchResultResource;
use App\Models\PacketSearchResult;
use Illuminate\Support\Facades\Route;

// GET /api/fileextension
Route::middleware('auth:sanctum')->get('/packet-search-result', function () {
    return new PacketSearchResultCollection(PacketSearchResult::paginate());
});

// GET /api/fileextension/:id
Route::middleware('auth:sanctum')->get('/packet-search-result/{id}', function (string $id) {
    return new PacketSearchResultResource(PacketSearchResult::findOrFail($id));
});
