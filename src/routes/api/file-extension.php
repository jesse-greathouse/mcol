<?php

use App\Packet\File\FileExtension;
use Illuminate\Support\Facades\Route;

// GET /api/media-dynamic-range
Route::middleware('auth:sanctum')->get('/file-extension', function () {
    return FileExtension::getFileExtensions();
});
