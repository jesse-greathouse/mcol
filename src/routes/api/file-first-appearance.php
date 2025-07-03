<?php

use App\Http\Resources\FileFirstAppearanceCollection;
use App\Http\Resources\FileFirstAppearanceResource;
use App\Models\FileFirstAppearance;
use Illuminate\Support\Facades\Route;

// GET /api/fileextension
Route::middleware('auth:sanctum')->get('/file-first-appearance', function () {
    return new FileFirstAppearanceCollection(FileFirstAppearance::paginate());
});

// GET /api/fileextension/:id
Route::middleware('auth:sanctum')->get('/file-first-appearance/{id}', function (string $id) {
    return new FileFirstAppearanceResource(FileFirstAppearance::findOrFail($id));
});
