<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    Illuminate\Pagination\LengthAwarePaginator;

use Inertia\Inertia;

use App\Models\Download,
    App\Models\Instance,
    App\Packet\DownloadQueue,
    App\Packet\DownloadQueueRequestHandler as Handler;

/**
 * Handles download queue-related actions.
 */
class DownloadQueueController
{
    /**
     * Displays the download queue page.
     *
     * @param Request $request The incoming request.
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        // Apply any custom request overrides for the download queue.
        $this->downloadQueueOverrides($request);

        // Instantiate the queue handler.
        $queueHandler = new Handler($request);

        // Retrieve filters and statuses.
        $filters = $queueHandler->getFilters();
        $statuses = DownloadQueue::getStatusOptions();
        $instanceList = $this->getInstanceList();

        // Paginate the download queue data.
        $resp = $queueHandler->paginate([
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => 'page',
        ])->withQueryString()->toArray();

        // Return the data to the front-end using Inertia.
        return Inertia::render('DownloadQueue', [
            'statuses'          => $statuses,
            'instances'         => $instanceList,
            'filters'           => $filters,
            'downloads'         => $resp['data'],
            'path'              => $resp['path'],
            'currentPage'       => $resp['current_page'],
            'fromRecord'        => $resp['from'],
            'toRecord'          => $resp['to'],
            'perPage'           => $resp['per_page'],
            'lastPage'          => $resp['last_page'],
            'totalPackets'      => $resp['total'],
            'paginationNav'     => $resp['links'],
            'firstPageUrl'      => $resp['first_page_url'],
            'lastPageUrl'       => $resp['last_page_url'],
            'prevPageUrl'       => $resp['prev_page_url'],
            'nextPageUrl'       => $resp['next_page_url'],
        ]);
    }

    /**
     * Returns a list of all instance IDs.
     *
     * @return array An array of instance IDs.
     */
    protected function getInstanceList(): array
    {
        return Instance::all()->pluck('id')->toArray();
    }

    /**
     * Applies manual request overrides for the download queue.
     *
     * @param Request $request The incoming request.
     * @return void
     */
    protected function downloadQueueOverrides(Request $request): void
    {
        // Include only queued and incomplete by default, unless a specific file_name is being queried.
        if (!$request->has(Handler::FILE_NAME_KEY) && strlen($request->get(Handler::FILE_NAME_KEY)) === 0) {
            if (!$request->has(Handler::IN_STATUSES_KEY) && !$request->has(Handler::OUT_STATUSES_KEY)) {
                $request->merge([Handler::IN_STATUSES_KEY => [Download::STATUS_INCOMPLETE, Download::STATUS_QUEUED]]);
            }
        }
    }
}
