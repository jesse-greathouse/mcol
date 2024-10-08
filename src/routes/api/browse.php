<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

use App\Http\Resources\BrowseCollection,
    App\Models\FileDownloadLock,
    App\Packet\BrowseRequestHandler as Handler,
    App\Packet\DownloadQueue;

// GET /api/browse
Route::middleware('auth:sanctum')->get('/browse', function (Request $request) {
    browseOverrides($request);

    $browseHandler = new Handler($request);
    return new BrowseCollection($browseHandler->paginate([
        'path' => Paginator::resolveCurrentPath(),
        'pageName' => 'page',
    ]));
});

// GET /api/browse/locks
Route::middleware('auth:sanctum')->get('/browse/locks', function (Request $request) {
    browseOverrides($request);

    $packetList = [];

    if ($request->has('packet_list')) {
        $packetList = $request->input('packet_list');
    }

    return [
        'locks'         => FileDownloadLock::all()->pluck('file_name')->toArray(),
        'queued'        => DownloadQueue::getQueuedDownloads($packetList),
        'incomplete'    => DownloadQueue::getIncompleteDownloads($packetList),
        'completed'     => DownloadQueue::getCompletedDownloads($packetList),
    ];
});

/**
 * Manual request parameters for the application to override the user.
 *
 * @param Request $request
 * @return void
 */
function browseOverrides(Request $request) {
    // Don't include Beast chat bots, a lot of them never work.
    $request->merge([Handler::OUT_NICK_KEY => ['Beast-']]);

    // Include all media types by default (excludes nulls).
    // if (!$request->has(Handler::IN_MEDIA_TYPE_KEY) && !$request->has(Handler::OUT_MEDIA_TYPE_KEY)) {
    //     $request->merge([Handler::IN_MEDIA_TYPE_KEY => MediaType::getMediaTypes()]);
    // }

    // Include all file extensions by default (excludes files misisng file extensions).
    // if (!$request->has(Handler::IN_FILE_EXTENSION_KEY) && !$request->has(Handler::OUT_FILE_EXTENSION_KEY)) {
    //     $fileExtensions = FileExtension::getFileExtensions();
    //     $request->merge([Handler::IN_FILE_EXTENSION_KEY => $fileExtensions]);
    // }
}
