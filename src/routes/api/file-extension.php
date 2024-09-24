<?php

use Illuminate\Support\Facades\Route;

use App\Models\FileExtension,
    App\Http\Resources\FileExtensionCollection,
    App\Http\Resources\FileExtensionResource;

// GET /api/fileextension
Route::middleware('auth:sanctum')->get('/file-extension', function () {
    return new FileExtensionCollection(FileExtension::all());
});

// GET /api/fileextension/:id
Route::middleware('auth:sanctum')->get('/file-extension/{id}', function (string $id) {
    return new FileExtensionResource(FileExtension::findOrFail($id));
});
