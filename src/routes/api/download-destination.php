<?php

use Illuminate\Http\Request,
    Illuminate\Support\Facades\Route;

use App\Http\Resources\DownloadDestinationResource,
    App\Http\Resources\DownloadDestinationCollection,
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

// GET /api/download-destination/:id
Route::middleware('auth:sanctum')->get('/download-destination/{id}', function (string $id) {
    return new DownloadDestinationResource(DownloadDestination::findOrFail($id));
});
