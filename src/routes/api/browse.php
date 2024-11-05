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
    $browseHandler = new Handler($request);
    return new BrowseCollection($browseHandler->paginate([
        'path' => Paginator::resolveCurrentPath(),
        'pageName' => 'page',
    ]));
});

// GET /api/browse/locks
Route::middleware('auth:sanctum')->get('/browse/locks', function (Request $request) {
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
