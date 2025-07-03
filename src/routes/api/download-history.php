<?php

use App\Http\Resources\DownloadHistoryCollection;
use App\Http\Resources\DownloadHistoryResource;
use App\Models\DownloadHistory;
use Illuminate\Support\Facades\Route;

// GET /api/download-history
Route::middleware('auth:sanctum')->get('/download-history', function () {
    return new DownloadHistoryCollection(DownloadHistory::paginate());
});

// GET /api/download-history/:id
Route::middleware('auth:sanctum')->get('/download-history/{id}', function (string $id) {
    return new DownloadHistoryResource(DownloadHistory::findOrFail($id));
});
