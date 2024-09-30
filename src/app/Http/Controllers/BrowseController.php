<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;

use App\Packet\BrowseRequestHandler as Handler,
    App\Packet\File\FileExtension,
    App\Media\MediaLanguage,
    App\Media\MediaType;

class BrowseController
{
    public function index(Request $request)
    {
        overrides($request);
        $browseHandler = new Handler($request);
        
        $resp = $browseHandler->paginate([
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => 'page',
        ])->toArray();
        $filters = $browseHandler->getFilters();
        $mediaTypes = MediaType::getMediaTypes();
        $languages = MediaLanguage::getMediaLanguages();

        return Inertia::render('Browse', [
            'media_types'       => $mediaTypes,
            'languages'         => $languages,
            'filters'           => $filters,
            'packets'           => $resp['data'],
            'path'              => $resp['path'],
            'current_page'      => $resp['current_page'],
            'from_record'       => $resp['from'],
            'to_record'         => $resp['to'],
            'per_page'          => $resp['per_page'],
            'last_page'         => $resp['last_page'],
            'total_packets'     => $resp['total'],
            'pagination_nav'    => $resp['links'],
            'first_page_url'    => $resp['first_page_url'],
            'last_page_url'     => $resp['last_page_url'],
            'prev_page_url'     => $resp['prev_page_url'],
            'next_page_url'     => $resp['next_page_url'],
        ]);
    }
}

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

    // Include all file extensions by default (excludes files misisng file extensions).
    if (!$request->has(Handler::IN_FILE_EXTENSION_KEY) && !$request->has(Handler::OUT_FILE_EXTENSION_KEY)) {
        $fileExtensions = FileExtension::getFileExtensions();
        $request->merge([Handler::IN_FILE_EXTENSION_KEY => $fileExtensions]);
    }
}
