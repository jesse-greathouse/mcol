<?php

namespace App\Http\Controllers;

use App\Media\MediaResolution;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;

use App\Packet\BrowseRequestHandler as Handler,
    App\Packet\DownloadQueue,
    App\Packet\File\FileExtension,
    App\Media\MediaDynamicRange,
    App\Media\MediaLanguage,
    App\Media\MediaType,
    App\Models\Download,
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
        $locks = FileDownloadLock::all()->pluck('file_name')->toArray();
        
        $resp = $browseHandler->paginate([
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => 'page',
        ])->withQueryString()->toArray();

        $packetList = $this->getPacketList($resp['data']);
        $queued = $this->getQueuedDownloads($packetList);
        $incomplete = $this->getIncompleteDownloads($packetList);
        $completed = $this->getCompletedDownloads($packetList);
        $queue = DownloadQueue::getQueue();

        return Inertia::render('Browse', [
            'queue'             => $queue,
            'locks'             => $locks,
            'queued'            => $queued,
            'incomplete'        => $incomplete,
            'completed'         => $completed,
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
     * Returns a dictionary of files queued for download with the filename as the key.
     *
     * @param array $packetList
     * @return Collection
     */
    protected function getQueuedDownloads(array $packetList = []): Collection
    {
        return $this->getDownloads(Download::STATUS_QUEUED, $packetList);
    }

    /**
     * Returns a dictionary of downloads in progress with the filename as the key.
     *
     * @param array $packetList
     * @return Collection
     */
    protected function getIncompleteDownloads(array $packetList = []): Collection
    {
        return $this->getDownloads(Download::STATUS_INCOMPLETE, $packetList);
    }

    /**
     * Returns a dictionary of completed downloaded files with the filename as the key.
     * Only includes the files that appear in $packetList.
     *
     * @param array $packetList
     * @return Collection
     */
    protected function getCompletedDownloads(array $packetList = []): Collection
    {
        return $this->getDownloads(Download::STATUS_COMPLETED, $packetList);
    }

    /**
     * Returns a dictionary of Download objects with the file name as the key.
     * Can filter by a list of packet IDs ($packetList), and status.
     *
     * [
     *  'foo.mkv' => Download,
     *  'bar.mkv'  => Download,
     *  ...
     * ]
     * @param string $status
     * @param array $packetList
     * @return Collection
     */
    protected function getDownloads(string $status = null, array $packetList = []): Collection
    {
        $qb = Download::join('packets', 'packets.id', '=', 'downloads.packet_id')
            ->join ('file_download_locks', 'file_download_locks.file_name', 'packets.file_name');

        if (0 < count($packetList)) {
            $qb->whereIn('packet_id', $packetList);
        }

        if (null !== $status) {
            $qb->where('status', $status);
        }

        return $qb->get([
            'downloads.id',
            'packets.file_name',
            'downloads.packet_id',
            'downloads.status',
            'downloads.queued_status',
            'downloads.queued_total',
            'downloads.file_size_bytes',
            'downloads.progress_bytes',
            'downloads.file_uri',
            'downloads.created_at',
            'downloads.updated_at',
        ])->mapWithKeys(function (Download $download, int $key) {
            $fileName = basename($download->file_uri);
            return [$fileName => $download];
        });
    }

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
    if (!$request->has(Handler::IN_MEDIA_TYPE_KEY) && !$request->has(Handler::OUT_MEDIA_TYPE_KEY)) {
        $request->merge([Handler::IN_MEDIA_TYPE_KEY => MediaType::getMediaTypes()]);
    }

    // Include all file extensions by default (excludes files misisng file extensions).
    if (!$request->has(Handler::IN_FILE_EXTENSION_KEY) && !$request->has(Handler::OUT_FILE_EXTENSION_KEY)) {
        $fileExtensions = FileExtension::getFileExtensions();
        $request->merge([Handler::IN_FILE_EXTENSION_KEY => $fileExtensions]);
    }
}
