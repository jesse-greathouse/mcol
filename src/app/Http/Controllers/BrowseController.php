<?php

namespace App\Http\Controllers;

use App\Media\MediaResolution;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;

use App\Packet\BrowseRequestHandler as Handler,
    App\Packet\DownloadQueue,
    App\Media\MediaDynamicRange,
    App\Media\MediaLanguage,
    App\Media\MediaType,
    App\Models\FileDownloadLock;

class BrowseController
{
    public function index(Request $request)
    {
        browseOverrides($request);
        $browseHandler = new Handler($request);

        $filters = $browseHandler->getFilters();
        $mediaTypes = MediaType::getMediaTypes();
        $resolutions = MediaResolution::getMediaResolutions();
        $languages = MediaLanguage::getMediaLanguages();
        $dynamicRanges = MediaDynamicRange::getMediaDynamicRanges();
        
        $resp = $browseHandler->paginate([
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => 'page',
        ])->withQueryString()->toArray();

        $packetList = $this->getPacketList($resp['data']);

        return Inertia::render('Browse', [
            'packet_list'       => $packetList,
            'locks'             => fn () => FileDownloadLock::all()->pluck('file_name')->toArray(),
            'queue'             => fn () => DownloadQueue::getQueue(),
            'queued'            => fn () => DownloadQueue::getQueuedDownloads($packetList),
            'incomplete'        => fn () => DownloadQueue::getIncompleteDownloads($packetList),
            'completed'         => fn () => DownloadQueue::getCompletedDownloads($packetList),
            'dynamic_ranges'    => $dynamicRanges,
            'media_types'       => $mediaTypes,
            'resolutions'       => $resolutions,
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

    /**
     * With an array of packets, convert it into a list of packet IDs.
     *
     * @param array $packets
     * @return array
     */
    protected function getPacketList(array $packets): array
    {
        $list = [];
        foreach($packets as $packet) {
            $list[] = $packet->id;
        }

        return $list;
    }
}

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

    // // Include all file extensions by default (excludes files misisng file extensions).
    // if (!$request->has(Handler::IN_FILE_EXTENSION_KEY) && !$request->has(Handler::OUT_FILE_EXTENSION_KEY)) {
    //     $fileExtensions = FileExtension::getFileExtensions();
    //     $request->merge([Handler::IN_FILE_EXTENSION_KEY => $fileExtensions]);
    // }
}
