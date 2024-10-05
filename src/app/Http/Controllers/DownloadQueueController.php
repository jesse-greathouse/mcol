<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    Illuminate\Pagination\LengthAwarePaginator;

use Inertia\Inertia;

use App\Models\Download,
    App\Models\Instance,
    App\Packet\DownloadQueue,
    App\Packet\DownloadQueueRequestHandler as Handler;

class DownloadQueueController
{
    public function index(Request $request)
    {
        downloadQueueOverrides($request);
        $browseHandler = new Handler($request);

        $filters = $browseHandler->getFilters();
        $statuses = DownloadQueue::getStatusOptions();
        $instanceList = $this->getInstanceList();
        
        $resp = $browseHandler->paginate([
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => 'page',
        ])->withQueryString()->toArray();

        return Inertia::render('DownloadQueue', [
            'statuses'          => $statuses,
            'instances'         => $instanceList,
            'filters'           => $filters,
            'downloads'         => $resp['data'],
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
     * Returns a list of all instance IDs
     *
     * @return array
     */
    protected function getInstanceList(): array
    {
        return Instance::all()->pluck('id')->toArray();
    }
}

/**
 * Manual request parameters for the application to override the user.
 *
 * @param Request $request
 * @return void
 */
function downloadQueueOverrides(Request $request) {
    // Include only queued and incomplete by default.
    // Only if file_name is not specifically being queried.
    if (!$request->has(Handler::FILE_NAME_KEY) && (1 > strlen($request->has(Handler::FILE_NAME_KEY)))) {
        if (!$request->has(Handler::IN_STATUSES_KEY) && !$request->has(Handler::OUT_STATUSES_KEY)) {
            $request->merge([Handler::IN_STATUSES_KEY => [Download::STATUS_INCOMPLETE, Download::STATUS_QUEUED]]);
        }
    }
}
