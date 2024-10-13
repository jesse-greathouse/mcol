<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

use App\Http\Resources\DownloadQueueCollection,
    App\Models\Download,
    App\Packet\DownloadQueueRequestHandler as Handler;

// GET /api/download-queue
Route::middleware('auth:sanctum')->get('/download-queue', function (Request $request) {
    apiDownloadQueueOverrides($request);

    $browseHandler = new Handler($request);
    return new DownloadQueueCollection($browseHandler->paginate([
        'path' => Paginator::resolveCurrentPath(),
        'pageName' => 'page',
    ]));
});

// GET /api/download-queue/queue
Route::middleware('auth:sanctum')->get('/download-queue/queue', function (Request $request) {
    apiDownloadQueueOverrides($request);

    $browseHandler = new Handler($request);
    return $browseHandler->queue();
});

/**
 * Manual request parameters for the application to override the user.
 *
 * @param Request $request
 * @return void
 */
function apiDownloadQueueOverrides(Request $request) {
    // Include only queued and incomplete by default.
    // Only if file_name is not specifically being queried.
    if (!$request->has(Handler::FILE_NAME_KEY) && (1 > strlen($request->has(Handler::FILE_NAME_KEY)))) {
        if (!$request->has(Handler::IN_STATUSES_KEY) && !$request->has(Handler::OUT_STATUSES_KEY)) {
            $request->merge([Handler::IN_STATUSES_KEY => [Download::STATUS_INCOMPLETE, Download::STATUS_QUEUED]]);
        }
    }
}
