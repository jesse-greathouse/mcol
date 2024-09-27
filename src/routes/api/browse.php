<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

use App\Http\Resources\BrowseCollection,
    App\Media\MediaType,
    App\Models\FileExtension,
    App\Packet\BrowseRequestHandler as Handler;

// GET /api/browse
Route::middleware('auth:sanctum')->get('/browse', function (Request $request) {
    overrides($request);

    $browseHandler = new Handler($request);
    return new BrowseCollection($browseHandler->paginate([
        'path' => Paginator::resolveCurrentPath(),
        'pageName' => 'page',
    ]));
});

/**
 * Manual request parameters for the application to override the user.
 *
 * @param Request $request
 * @return void
 */
function overrides(Request $request) {
    // Don't include Beast chat bots, a lot of them never work.
    $request->merge([Handler::OUT_NICK_KEY => ['Beast-']]);

    // Include all media types by default (excludes nulls).
    if (!$request->has(Handler::IN_MEDIA_TYPE_KEY) && !$request->has(Handler::OUT_MEDIA_TYPE_KEY)) {
        $request->merge([Handler::IN_MEDIA_TYPE_KEY => MediaType::getMediaTypes()]);
    }
}
