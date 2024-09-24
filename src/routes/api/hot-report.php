<?php

use Illuminate\Support\Facades\Route;

use App\Models\HotReport,
    App\Http\Resources\HotReportCollection,
    App\Http\Resources\HotReportResource;

// GET /api/fileextension
Route::middleware('auth:sanctum')->get('/hot-report', function () {
    return new HotReportCollection(HotReport::paginate());
});

// GET /api/fileextension/:id
Route::middleware('auth:sanctum')->get('/hot-report/{id}', function (string $id) {
    return new HotReportResource(HotReport::findOrFail($id));
});
