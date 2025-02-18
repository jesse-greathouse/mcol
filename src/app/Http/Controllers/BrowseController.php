<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

use Illuminate\Http\Request,
    Illuminate\Pagination\LengthAwarePaginator;

use App\Packet\BrowseRequestHandler as Handler,
    App\Packet\DownloadQueue,
    App\Media\MediaDynamicRange,
    App\Media\MediaLanguage,
    App\Media\MediaResolution,
    App\Media\MediaType,
    App\Models\Network,
    App\Models\FileDownloadLock,
    App\Settings;

/**
 * Handles browsing and pagination of available packets.
 */
class BrowseController
{
    /**
     * Display the browse view with paginated data.
     *
     * @param Request $request The request object containing the query parameters.
     * @return \Inertia\Response The Inertia response rendering the Browse view.
     */
    public function index(Request $request)
    {
        // Initialize browse handler with the current request.
        $browseHandler = new Handler($request);

        // Get paginated response data with current path and query string.
        $paginationData = $browseHandler->paginate([
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => 'page',
        ])->withQueryString()->toArray();

        // Extract packet IDs from paginated response data.
        $packetList = array_map(fn ($packet) => $packet->id, $paginationData['data']);

        return Inertia::render('Browse', [
            'packet_list'       => $packetList, // List of packet IDs for rendering.
            'settings'          => fn (Settings $settings) => $settings->toArray(), // Get settings as array.
            'locks'             => fn () => FileDownloadLock::all()->pluck('file_name')->toArray(), // List of locked files.
            'queue'             => fn () => DownloadQueue::getQueue(), // Get the download queue.
            'queued'            => fn () => DownloadQueue::getQueuedDownloads($packetList), // Get queued downloads.
            'incomplete'        => fn () => DownloadQueue::getIncompleteDownloads($packetList), // Get incomplete downloads.
            'completed'         => fn () => DownloadQueue::getCompletedDownloads($packetList), // Get completed downloads.
            'networks'          => fn () => Network::all()->pluck('name')->toArray(), // List of network names.
            'dynamic_ranges'    => fn () => MediaDynamicRange::getMediaDynamicRanges(), // Get dynamic ranges for media.
            'media_types'       => fn () => MediaType::getMediaTypes(), // List of media types.
            'resolutions'       => fn () => MediaResolution::getMediaResolutions(), // Get media resolutions.
            'languages'         => fn () => MediaLanguage::getMediaLanguages(), // List of media languages.
            'filters'           => fn () => $browseHandler->getFilters(), // Get filters from the browse handler.
            'packets'           => $paginationData['data'], // Full packet data for rendering.
            'path'              => $paginationData['path'], // Current path for pagination.
            'current_page'      => $paginationData['current_page'], // Current page in the pagination.
            'from_record'       => $paginationData['from'], // Starting record in the pagination.
            'to_record'         => $paginationData['to'], // Ending record in the pagination.
            'per_page'          => $paginationData['per_page'], // Number of items per page.
            'last_page'         => $paginationData['last_page'], // Last page number.
            'total_packets'     => $paginationData['total'], // Total number of packets.
            'pagination_nav'    => $paginationData['links'], // Pagination navigation links.
            'first_page_url'    => $paginationData['first_page_url'], // First page URL.
            'last_page_url'     => $paginationData['last_page_url'], // Last page URL.
            'prev_page_url'     => $paginationData['prev_page_url'], // Previous page URL.
            'next_page_url'     => $paginationData['next_page_url'], // Next page URL.
        ]);
    }
}
