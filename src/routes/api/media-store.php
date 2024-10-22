<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Exceptions\DirectoryNotWithinMediaStoreException,
    App\Exceptions\FileNotFoundException,
    App\Exceptions\MediaStoreDirectoryIndexOutOfBoundsException,
    App\Exceptions\SettingsIllegalStoreException,
    App\Exceptions\UriHasDotSlashException,
    App\Http\Requests\CreateDirectoryRequest,
    App\Http\Resources\MediaStoreCollection,
    App\Http\Resources\MediaStoreResource,
    App\Media\Store,
    App\Settings;

// GET /api/media-store/{name}
Route::middleware('auth:sanctum')->get('/media-store/{name}', function (string $name, Request $request, Settings $settings) {
    $store = new Store($settings->media_store);
    $index = 0;

    if ($request->has('index') && is_numeric($request->input('index'))) {
        $index = $request->input('index');
    }

    try {
        $resp =$store->getStoreRootDir($name, $index);
    } catch(MediaStoreDirectoryIndexOutOfBoundsException | SettingsIllegalStoreException $e) {
        return response($e->getMessage(), 400);
    }

    return new MediaStoreCollection($resp);
});

// POST /api/media-store
Route::middleware('auth:sanctum')->post('/media-store', function (CreateDirectoryRequest $request, Settings $settings) {
    ['uri' => $uri ] = $request->validated();
    $store = new Store($settings->media_store);
    $dir = $store->createDir($uri);

    return new MediaStoreResource($dir);
});

// GET /api/media-store?uri=/some/file/path
Route::middleware('auth:sanctum')->get('/media-store', function (Request $request, Settings $settings) {
    if (!$request->has('uri')) {
        return response('uri parameter is required.', 400);
    }

    $uri = $request->input('uri');

    $store = new Store($settings->media_store);

    try {
        $resp = $store->getDir($uri);
    } catch(DirectoryNotWithinMediaStoreException | UriHasDotSlashException $e) {
        return response($e->getMessage(), 400);
    }

    return new MediaStoreCollection($resp);
})->name('api-media-store');

// DEL /api/media-store?uri=/some/file/path
Route::middleware('auth:sanctum')->delete('/media-store', function (Request $request, Settings $settings) {
    if (!$request->has('uri')) {
        return response('uri parameter is required.', 400);
    }

    $uri = $request->input('uri');

    $store = new Store($settings->media_store);

    try {
        $store->rm($uri);
    } catch(DirectoryNotWithinMediaStoreException | UriHasDotSlashException | FileNotFoundException $e) {
        return response($e->getMessage(), 400);
    }

    return response()->json([
        'success' => true,
        'message' => "$uri was deleted."
    ]);
});
