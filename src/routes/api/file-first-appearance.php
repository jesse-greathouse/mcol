<?php

use Illuminate\Support\Facades\Route;

use App\Models\FileFirstAppearance,
    App\Http\Resources\FileFirstAppearanceCollection,
    App\Http\Resources\FileFirstAppearanceResource;

// GET /api/fileextension
Route::middleware('auth:sanctum')->get('/file-first-appearance', function () {
    return new FileFirstAppearanceCollection(FileFirstAppearance::paginate());
});

// GET /api/fileextension/:id
Route::middleware('auth:sanctum')->get('/file-first-appearance/{id}', function (string $id) {
    return new FileFirstAppearanceResource(FileFirstAppearance::findOrFail($id));
});
