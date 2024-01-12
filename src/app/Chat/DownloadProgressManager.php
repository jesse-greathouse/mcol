<?php

namespace App\Chat;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

use App\Models\Instance,
    App\Models\Download;

class DownloadProgressManager
{

    const ANNOUNCE_INTERVAL = 180; # 3 minutes

    /**
     * timestamp of last announcment.
     *
     * @var integer
     */
    protected $lastAnnounce;

    /**
     * Console client
     * 
     * @var Command
     */
    protected $console;

    /**
     * Instance of chat client
     * 
     * @var Instance
     */
    protected $instance;

    public function __construct(Instance $instance, Command $console)
    {
        $this->instance = $instance;
        $this->console = $console;
    }

    /**
     * Reports progress of all downloads queued in this instance.
     *
     * @return void
     */
    public function reportProgress(): void
    {
        if ($this->shouldAnnounce()) {
            $downloads = $this->getDownloads();
            $completeds = $this->getCompleteds();
            $headers = ['File Download Queue', 'Progress', 'Downloaded', 'File Size'];
            $body = [];

            foreach($downloads as $download) {
                $progress = "? %";

                if ($download->isQueued()) {
                    $total = (null === $download->queued_status) ? '?' : $download->queued_total;
                    $progress = " {$download->queued_status} / $total";
                } else if ($download->file_size_bytes && $download->progress_bytes) {
                    $num = (string) ceil(($download->progress_bytes / ($download->file_size_bytes * 10)) * 100);
                    $progress = "$num %";
                }

                $body[] = [
                    basename($download->file_uri),
                    $progress,
                    $download->progress_bytes,
                    $download->file_size_bytes,
                ];
            }

            // Sort Report
            usort($body, [$this, 'sortRows']);

            // Add Completeds after sort so they're at the bottom.
            foreach($completeds as $completed) {
                // Only list completed if the file still exists.
                if (file_exists($completed->file_uri)) {
                    $body[] = [
                        basename($completed->file_uri),
                        'Done',
                        $completed->progress_bytes,
                        $completed->file_size_bytes,
                    ];
                }
            }

            if (count($body) > 0) {
                $this->console->table($headers, $body);
            }
        }
    }

    /**
     * Used for sorting the report rows.
     *
     * @param array $a
     * @param array $b
     * @return void
     */
    public function sortRows(array $a, array $b)
    {
        // Sort on the progress column.
        return strcmp(substr($b[1], 0, 5), substr($a[1], 0, 5));
    }

    /**
     * Instantiates a query builder dynamically given the various inputs.
     *
     * @return Collection
     */
    public function getDownloads(): Collection
    {
        return Download::join('packets', 'packets.id', '=', 'downloads.packet_id')
                ->join('networks', 'networks.id', 'packets.network_id')
                ->join ('clients', 'clients.network_id', 'networks.id')
                ->join ('instances', 'instances.client_id', 'clients.id')
                ->where('instances.id', $this->instance->id)
                ->where('downloads.status', Download::STATUS_INCOMPLETE)
                ->orWhere('downloads.status', Download::STATUS_QUEUED)
                ->get([
                    'downloads.id',
                    'downloads.status',
                    'downloads.queued_status',
                    'downloads.queued_total',
                    'downloads.file_size_bytes',
                    'downloads.progress_bytes',
                    'downloads.file_uri',
                ]);
    }

    /**
     * Instantiates a query builder dynamically given the various inputs.
     *
     * @return Collection
     */
    public function getCompleteds(): Collection
    {
        return Download::join('packets', 'packets.id', '=', 'downloads.packet_id')
                ->join('networks', 'networks.id', 'packets.network_id')
                ->join ('clients', 'clients.network_id', 'networks.id')
                ->join ('instances', 'instances.client_id', 'clients.id')
                ->where('instances.id', $this->instance->id)
                ->where('downloads.status', Download::STATUS_COMPLETED)
                ->orderBy('downloads.updated_at')
                ->get([
                    'downloads.id',
                    'downloads.status',
                    'downloads.queued_status',
                    'downloads.queued_total',
                    'downloads.file_size_bytes',
                    'downloads.progress_bytes',
                    'downloads.file_uri',
                ]);
    }

    /**
     * Should the DownloadProgressManager announce its reporting.
     *
     * @return boolean
     */
    public function shouldAnnounce(): bool
    {
        $now = time();
        
        if (null === $this->lastAnnounce) {
            $this->lastAnnounce = $now;
            return true;
        }

        $interval = $now - $this->lastAnnounce;

        if ($interval >= self::ANNOUNCE_INTERVAL) {
            $this->lastAnnounce = $now;
            return true;
        }

        return false;
    }
}
