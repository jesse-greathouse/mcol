<?php

use App\Media\MediaDynamicRange;
use App\Media\MediaLanguage;
use App\Media\MediaResolution;
use App\Media\MediaType;
use Illuminate\Support\Facades\Route;

// GET /api/media-dynamic-range
Route::middleware('auth:sanctum')->get('/media-dynamic-range', function () {
    return MediaDynamicRange::getMediaDynamicRanges();
});

// GET /api/media-language
Route::middleware('auth:sanctum')->get('/media-language', function () {
    return MediaLanguage::getMediaLanguages();
});

// GET /api/media-resolution
Route::middleware('auth:sanctum')->get('/media-resolution', function () {
    return MediaResolution::getMediaResolutions();
});

// GET /api/media-type
Route::middleware('auth:sanctum')->get('/media-type', function () {
    return MediaType::getMediaTypes();
});
