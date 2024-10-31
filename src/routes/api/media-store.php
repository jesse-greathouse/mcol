<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request,
    Illuminate\Http\Exceptions\HttpResponseException;

use App\Exceptions\DirectoryDirectionSortIllegalOptionException,
    App\Exceptions\DirectoryNotWithinMediaStoreException,
    App\Exceptions\DirectoryRemoveMediaRootException,
    App\Exceptions\DirectorySortIllegalOptionException,
    App\Exceptions\FileNotFoundException,
    App\Exceptions\InvalidDirectoryException,
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
    $sort = Store::SORT_DEFAULT;
    $direction = Store::DIRECTION_SORT_DEFAULT;

    if ($request->has('index') && is_numeric($request->input('index'))) {
        $index = $request->input('index');
    }

    if ($request->has('sort')) {
        $sort = $request->input('sort');
    }

    if ($request->has('direction')) {
        $direction = $request->input('direction');
    }

    try {
        $resp =$store->getStoreRootDir($name, $index, $sort, $direction);
    } catch(
        DirectoryDirectionSortIllegalOptionException |
        DirectorySortIllegalOptionException |
        MediaStoreDirectoryIndexOutOfBoundsException |
        SettingsIllegalStoreException $e) {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => $e->getMessage(),
            'data'      => [
                'name'      => $name,
                'index'     => $index,
                'sort'      => $sort,
                'direction' => $direction,
            ]
        ], 400));
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
    $store = new Store($settings->media_store);
    $sort = Store::SORT_DEFAULT;
    $direction = Store::DIRECTION_SORT_DEFAULT;

    if (!$request->has('uri')) {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'uri parameter is required.',
            'data'      => []
        ], 400));
    }

    $uri = $request->input('uri');

    if ($request->has('sort')) {
        $sort = $request->input('sort');
    }

    if ($request->has('direction')) {
        $direction = $request->input('direction');
    }

    try {
        $resp = $store->getDir($uri, $sort, $direction);
    } catch(
        DirectoryDirectionSortIllegalOptionException |
        DirectorySortIllegalOptionException |
        DirectoryNotWithinMediaStoreException |
        InvalidDirectoryException |
        UriHasDotSlashException $e) {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => $e->getMessage(),
            'data'      => [
                'uri'  => $uri,
                'sort'      => $sort,
                'direction' => $direction,
            ]
        ], 400));
    }

    return new MediaStoreCollection($resp);
});

// DEL /api/media-store?uri=/some/file/path
Route::middleware('auth:sanctum')->delete('/media-store', function (Request $request, Settings $settings) {
    if (!$request->has('uri')) {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'uri parameter is required.',
            'data'      => []
        ], 400));
    }

    $uri = $request->input('uri');

    $store = new Store($settings->media_store);

    try {
        $store->rm($uri);
    } catch(DirectoryNotWithinMediaStoreException| DirectoryRemoveMediaRootException | UriHasDotSlashException | FileNotFoundException $e) {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => $e->getMessage(),
            'data'      => [
                'uri'  => $uri,
            ]
        ], 400));
    }

    return response()->json([
        'success' => true,
        'message' => "$uri was deleted."
    ]);
});
