<?php

use App\Http\Resources\DownloadQueueCollection;
use App\Models\Download;
use App\Packet\DownloadQueueRequestHandler as Handler;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Route;

// GET /api/download-queue
Route::middleware('auth:sanctum')->get('/download-queue', function (Request $request) {
    apiDownloadQueueOverrides($request);

    $queueHandler = new Handler($request);

    return new DownloadQueueCollection($queueHandler->paginate([
        'path' => Paginator::resolveCurrentPath(),
        'pageName' => 'page',
    ]));
});

// GET /api/download-queue/queue
Route::middleware('auth:sanctum')->get('/download-queue/queue', function (Request $request) {
    apiDownloadQueueOverrides($request);

    $queueHandler = new Handler($request);

    return $queueHandler->queue();
});

/**
 * Manual request parameters for the application to override the user.
 *
 * @return void
 */
function apiDownloadQueueOverrides(Request $request)
{
    // Include only queued and incomplete by default.
    // Only if file_name is not specifically being queried.
    if (! $request->has(Handler::FILE_NAME_KEY) && (strlen($request->has(Handler::FILE_NAME_KEY)) < 1)) {
        if (! $request->has(Handler::IN_STATUSES_KEY) && ! $request->has(Handler::OUT_STATUSES_KEY)) {
            $request->merge([Handler::IN_STATUSES_KEY => [Download::STATUS_INCOMPLETE, Download::STATUS_QUEUED]]);
        }
    }
}
