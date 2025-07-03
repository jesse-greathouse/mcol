<?php

use App\Http\Resources\HotReportCollection;
use App\Http\Resources\HotReportResource;
use App\Models\HotReport;
use Illuminate\Support\Facades\Route;

// GET /api/fileextension
Route::middleware('auth:sanctum')->get('/hot-report', function () {
    return new HotReportCollection(HotReport::paginate());
});

// GET /api/fileextension/:id
Route::middleware('auth:sanctum')->get('/hot-report/{id}', function (string $id) {
    return new HotReportResource(HotReport::findOrFail($id));
});
