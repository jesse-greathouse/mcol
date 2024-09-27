<?php

use Illuminate\Support\Facades\Route;

use App\Packet\File\FileExtension;

// GET /api/media-dynamic-range
Route::middleware('auth:sanctum')->get('/file-extension', function () {
    return FileExtension::getFileExtensions();
});
