<?php

use Illuminate\Support\Facades\Route;

use App\Models\Download,
    App\Http\Requests\ApiStoreDownloadRequest,
    App\Http\Resources\DownloadCollection,
    App\Http\Resources\DownloadResource;

// GET /api/download
Route::middleware('auth:sanctum')->get('/download', function () {
    return new DownloadCollection(Download::paginate());
});

// POST /api/download
Route::middleware('auth:sanctum')->post('/download', function (ApiStoreDownloadRequest $request) {
    $validated = $request->validated();

    $inputs = [
        'file_uri'  => $validated['file_uri'],
        'packet_id' => $validated['packet'],
    ];

    if (isset($validated['enabled'])) {
        $inputs['enabled'] = $validated['enabled'];
    }

    if (isset($validated['status'])) {
        $inputs['status'] = strtoupper($validated['status']);
    }

    if (isset($validated['file_size_bytes'])) {
        $inputs['file_size_bytes'] = $validated['file_size_bytes'];
    }

    if (isset($validated['progress_bytes'])) {
        $inputs['progress_bytes'] = $validated['progress_bytes'];
    }

    if (isset($validated['queued_total'])) {
        $inputs['queued_total'] = $validated['queued_total'];
    }

    if (isset($validated['queued_status'])) {
        $inputs['queued_status'] = $validated['queued_status'];
    }

    $download = Download::create($inputs);
 
    return redirect("/api/download/{$download->id}");
});

// GET /api/download/:id
Route::middleware('auth:sanctum')->get('/download/{id}', function (string $id) {
    return new DownloadResource(Download::findOrFail($id));
});

// PUT /api/download/:id
Route::middleware('auth:sanctum')->put('/download/{id}', function (string $id, ApiStoreDownloadRequest $request) {
    $download = Download::findOrFail($id);

    $validated = $request->validated();
    $download->file_uri = $validated['file_uri'];
    $download->packet_id = $validated['packet'];

    if (isset($validated['enabled'])) {
        $download->enabled = $validated['enabled'];
    }

    if (isset($validated['status'])) {
        $download->status = strtoupper($validated['status']);
    }

    if (isset($validated['file_size_bytes'])) {
        $download->file_size_bytes = $validated['file_size_bytes'];
    }

    if (isset($validated['progress_bytes'])) {
        $download->progress_bytes = $validated['progress_bytes'];
    }

    if (isset($validated['queued_total'])) {
        $download->queued_total = $validated['queued_total'];
    }

    if (isset($validated['queued_status'])) {
        $download->queued_status = $validated['queued_status'];
    }

    $download->save();

    return redirect("/api/download/$id");
});

// DEL /api/download/:id
Route::middleware('auth:sanctum')->delete('/download/{id}', function (string $id) {
    $download = Download::findOrFail($id);
    $file = $download->file_uri;
    $download->delete();

    return response()->json([
        'success' => true,
        'message' => "Download: $file with id: $id was deleted."
    ]);
});
