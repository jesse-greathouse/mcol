<?php

use Illuminate\Http\Request,
    Illuminate\Support\Facades\Route;

use App\Http\Resources\DownloadDestinationResource,
    App\Http\Resources\DownloadDestinationCollection,
    App\Http\Requests\ApiStoreDownloadDestinationRequest,
    App\Models\DownloadDestination;

// GET /api/download-destination
Route::middleware('auth:sanctum')->get('/download-destination', function (Request $request) {
    $statusOptions = DownloadDestination::getStatusOptions();
    $qb = DownloadDestination::query();

    // Handle querying by status as a url parameter
    // in_status = List of statuses that will be included.
    // out_status = List of statuses that will be excluded.
    if ($request->has('in_status') && is_array($request->has('in_status'))) {
        $statuses = array_intersect($request->input('in_status'), $statusOptions);
        if (0 < count($statuses)) {
            $qb = $qb->whereIn('status', $statuses);
        }
    } else if ($request->has('out_status') && is_array($request->has('out_status'))) {
        $statuses = array_intersect($request->input('out_status'), $statusOptions);
        if (0 < count($statuses)) {
            $qb = $qb->whereNotIn('status', $statuses);
        }
    }

    return new DownloadDestinationCollection($qb->paginate());
});

// POST /api/download-destination
Route::middleware('auth:sanctum')->post('/download-destination', function (ApiStoreDownloadDestinationRequest $request) {
    $validated = $request->validated();

    $inputs = [
        'destination_dir'   => $validated['destination_dir'],
        'download_id'       => $validated['download'],
    ];

    $downloadDestination = DownloadDestination::create($inputs);

    return redirect("/api/download-destination/{$downloadDestination->id}");
});

// GET /api/download-destination/:id
Route::middleware('auth:sanctum')->get('/download-destination/{id}', function (string $id) {
    return new DownloadDestinationResource(DownloadDestination::findOrFail($id));
});

// PUT /api/download-destination/:id
Route::middleware('auth:sanctum')->put('/download-destination/{id}', function (string $id, ApiStoreDownloadDestinationRequest $request) {
    $downloadDestination = DownloadDestination::findOrFail($id);

    $validated = $request->validated();
    $downloadDestination->destination_dir = $validated['destination_dir'];
    $downloadDestination->download_id = $validated['download'];

    $downloadDestination->save();

    return new DownloadDestinationResource($downloadDestination);
});

// DEL /api/download-destination/:id
Route::middleware('auth:sanctum')->delete('/download-destination/{id}', function (string $id) {
    $downloadDestination = DownloadDestination::findOrFail($id);
    $file = $downloadDestination->download->file_uri;
    $downloadDestination->delete();

    return response()->json([
        'success' => true,
        'message' => "Download destination of: $file with id: $id was deleted."
    ]);
});
