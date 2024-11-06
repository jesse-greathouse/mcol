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
    App\Models\Network,
    App\Models\FileDownloadLock,
    App\Settings;

class BrowseController
{
    public function index(Request $request)
    {
        $browseHandler = new Handler($request);

        $resp = $browseHandler->paginate([
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => 'page',
        ])->withQueryString()->toArray();

        $packetList = array_map(fn ($packet) => $packet->id, $resp['data']);

        return Inertia::render('Browse', [
            'packet_list'       => $packetList,
            'settings'          => fn (Settings $settings) => $settings->toArray(),
            'locks'             => fn () => FileDownloadLock::all()->pluck('file_name')->toArray(),
            'queue'             => fn () => DownloadQueue::getQueue(),
            'queued'            => fn () => DownloadQueue::getQueuedDownloads($packetList),
            'incomplete'        => fn () => DownloadQueue::getIncompleteDownloads($packetList),
            'completed'         => fn () => DownloadQueue::getCompletedDownloads($packetList),
            'networks'          => fn () => Network::all()->pluck('name')->toArray(),
            'dynamic_ranges'    => fn () => MediaDynamicRange::getMediaDynamicRanges(),
            'media_types'       => fn () => MediaType::getMediaTypes(),
            'resolutions'       => fn () => MediaResolution::getMediaResolutions(),
            'languages'         => fn () => MediaLanguage::getMediaLanguages(),
            'filters'           => fn () => $browseHandler->getFilters(),
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
