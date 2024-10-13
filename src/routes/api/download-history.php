<?php

use Illuminate\Support\Facades\Route;

use App\Http\Resources\DownloadHistoryResource,
    App\Http\Resources\DownloadHistoryCollection,
    App\Models\DownloadHistory;

// GET /api/download-history
Route::middleware('auth:sanctum')->get('/download-history', function () {
    return new DownloadHistoryCollection(DownloadHistory::paginate());
});

// GET /api/download-history/:id
Route::middleware('auth:sanctum')->get('/download-history/{id}', function (string $id) {
    return new DownloadHistoryResource(DownloadHistory::findOrFail($id));
});
