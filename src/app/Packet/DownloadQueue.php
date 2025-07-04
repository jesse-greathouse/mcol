<?php

namespace App\Packet;

use App\Exceptions\IllegalPageException;
use App\Exceptions\IllegalRppException;
use App\Models\Download;
use App\Models\Instance;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class DownloadQueue
{
    const MYSQL_TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    const ORDER_BY_CREATED = 'downloads.updated_at';

    const ORDER_BY_NAME = 'downloads.file_name';

    const ORDER_BY_STATUS = 'downloads.status';

    const ORDER_BY_QUEUE = 'downloads.queued_status';

    const ORDER_OPTION_CREATED = 'created';

    const ORDER_OPTION_STATUS = 'status';

    const ORDER_OPTION_NAME = 'file_name';

    const ORDER_OPTION_QUEUE = 'queue';

    const ORDER_OPTION_DEFAULT = self::ORDER_OPTION_QUEUE;

    const ORDER_ASCENDING = 'asc';

    const ORDER_DESCENDING = 'desc';

    const DEFAULT_LOCKED = true;

    const DEFAULT_RPP = 40;

    const DEFAULT_PAGE = 1;

    public static array $columns = [
        'downloads.id',
        'downloads.file_name',
        'downloads.media_type',
        'bots.nick',
        'downloads.packet_id',
        'downloads.status',
        'downloads.queued_status',
        'downloads.queued_total',
        'downloads.file_size_bytes',
        'downloads.progress_bytes',
        'downloads.file_uri',
        'downloads.meta',
        'downloads.created_at',
        'downloads.updated_at',
    ];

    /**
     * List of Statuses to filter In
     *
     * @var array<string>
     */
    protected array $filterInStatuses = [];

    /**
     * List of Statuses to filter out
     *
     * @var array<string>
     */
    protected array $filterOutStatuses = [];

    /**
     * List of Instance IDs to filter in.
     *
     * @var array<int>
     */
    protected array $filterInInstances = [];

    /**
     * List of Instance IDs to filter out.
     *
     * @var array<int>
     */
    protected array $filterOutInstances = [];

    /**
     * Whether to include only files that are locked.
     *
     * @var bool
     */
    protected $filterLocked;

    /**
     * Name of file to filter by.
     *
     * @var string
     */
    protected $filterFileName;

    /**
     * Earlist DateTime of query.
     *
     * @var DateTime
     */
    protected $startDate;

    /**
     * Latest DateTime of query.
     *
     * @var DateTime
     */
    protected $endDate;

    /**
     * Holds the value of the order.
     *
     * @var string
     */
    protected $order;

    /**
     * Direction of result order.
     *
     * @var string
     */
    protected $direction;

    /**
     * Records per page.
     *
     * @var int
     */
    protected $rpp;

    /**
     * Page of the recordset.
     *
     * @var int
     */
    protected $page;

    /**
     * Instansiates a Download object.
     * Download is a comprehensive tool for creating SQL queries for downloading files.
     */
    public function __construct()
    {
        $this->order = self::ORDER_OPTION_QUEUE;
        $this->rpp = self::DEFAULT_RPP;
        $this->page = self::DEFAULT_PAGE;
        $this->filterLocked = self::DEFAULT_LOCKED;
    }

    /**
     * Static function for calling the queue method.
     */
    public static function getQueue(): array
    {
        $downloadQueue = new self;

        return $downloadQueue->queue();
    }

    /**
     * Returns a list of available order directions.
     */
    public static function getDirectionOptions(): array
    {
        return [
            self::ORDER_ASCENDING,
            self::ORDER_DESCENDING,
        ];
    }

    /**
     * Returns a list of available order options.
     */
    public static function getOrderOptions(): array
    {
        return [
            self::ORDER_OPTION_CREATED,
            self::ORDER_OPTION_STATUS,
            self::ORDER_OPTION_NAME,
            self::ORDER_OPTION_QUEUE,
        ];
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
     */
    public static function getDownloads(?string $status, array $packetList = []): Collection
    {
        $qb = Download::join('packets', 'packets.id', '=', 'downloads.packet_id')
            ->join('file_download_locks', 'file_download_locks.file_name', 'downloads.file_name')
            ->join('bots', 'bots.id', 'packets.bot_id');

        if (count($packetList) > 0) {
            $qb->whereIn('packet_id', $packetList);
        }

        if ($status !== null) {
            $qb->where('status', $status);
        }

        return $qb->get(self::$columns)
            ->load('packet')
            ->load('destination')
            ->mapWithKeys(function (Download $download, int $key) {
                $fileName = basename($download->file_uri);

                return [$fileName => $download];
            });
    }

    /**
     * Returns a dictionary of files queued for download with the filename as the key.
     */
    public static function getQueuedDownloads(array $packetList = []): Collection
    {
        return self::getDownloads(Download::STATUS_QUEUED, $packetList);
    }

    /**
     * Returns a dictionary of downloads in progress with the filename as the key.
     */
    public static function getIncompleteDownloads(array $packetList = []): Collection
    {
        return self::getDownloads(Download::STATUS_INCOMPLETE, $packetList);
    }

    /**
     * Returns a dictionary of completed downloaded files with the filename as the key.
     * Only includes the files that appear in $packetList.
     */
    public static function getCompletedDownloads(array $packetList = []): Collection
    {
        return self::getDownloads(Download::STATUS_COMPLETED, $packetList);
    }

    /**
     * Creates a dictionary of collections by the queue status.
     * Attempts to represent the state of all queued/downloading/completed files.
     * [
     *  'queued'        => [],
     *  'completed'     => [],
     *  'incomplete'    => [],
     * ]
     */
    public function queue(): array
    {
        $queue = [
            Download::STATUS_QUEUED => [],
            Download::STATUS_COMPLETED => [],
            Download::STATUS_INCOMPLETE => [],
        ];

        $downloads = $this->makeQueueQuery()
            ->get(self::$columns)
            ->load('packet')
            ->load('destination')
            ->toArray();

        foreach ($downloads as $download) {
            // Only add the download if it has a valid status option.
            if (in_array($download['status'], self::getStatusOptions())) {
                $queue[$download['status']][] = $download;
            }
        }

        return $queue;
    }

    /**
     * Run the Query and return a single model instance.
     */
    public function first(): ?Model
    {
        return $this->makeQuery()->first(self::$columns);
    }

    /**
     * Run the Query and return the result.
     */
    public function get(): Collection
    {
        return $this->makeQuery()->get(self::$columns);
    }

    /**
     * Run the Query and return paginated results.
     */
    public function paginate(array $options = []): LengthAwarePaginator
    {
        $total = $this->getQueryTotal();
        $items = $this->makeOffsetQuery()->get(self::$columns);
        $paginator = new LengthAwarePaginator($items, $total, $this->rpp, $this->page, $options);

        return $paginator;
    }

    /**
     * Makes a query specifically to represent the active queue.
     */
    public function makeQueueQuery(): Builder
    {
        // temporarily force the locked property to be true.
        $oldLocked = $this->getFilterLocked();
        $this->setFilterLocked('true');
        $qb = $this->getQuerySelect();
        $this->setFilterLocked($oldLocked);

        $qb = $qb->whereIn('downloads.status', self::getStatusOptions());
        $qb = $qb->orderBy(self::ORDER_BY_QUEUE, 'asc');
        $qb = $qb->orderBy(self::ORDER_BY_NAME, $this->getDefaultDirection());

        return $qb;
    }

    /**
     * Creates a query builder instance based on all the search and filtering criteria.
     */
    public function makeQuery(): Builder
    {
        $qb = $this->getQuerySelect();
        $qb = $this->getQueryFilters($qb);
        $qb = $this->getQueryOrder($qb);

        return $qb;
    }

    /**
     * Creates a query builder based on all the search and filtering criteria.
     */
    public function makeOffsetQuery(): Builder
    {
        $qb = $this->getQuerySelect();
        $qb = $this->getQueryFilters($qb);
        $qb = $this->getQueryOrder($qb);
        $qb = $this->getQueryOffset($qb);

        return $qb;
    }

    /**
     * Retrieves a total count of all the records in the result.
     */
    public function getQueryTotal(): string
    {
        $qb = $this->getQuerySelect();
        $qb = $this->getQueryFilters($qb);

        return $qb->count();
    }

    /**
     * Returns a list of available status options.
     */
    public static function getStatusOptions(): array
    {
        return [
            Download::STATUS_COMPLETED,
            Download::STATUS_INCOMPLETE,
            Download::STATUS_QUEUED,
        ];
    }

    /**
     * Maps order options to order values
     */
    protected function getOrderMap(): array
    {
        return [
            self::ORDER_OPTION_CREATED => self::ORDER_BY_CREATED,
            self::ORDER_OPTION_NAME => self::ORDER_BY_NAME,
            self::ORDER_OPTION_STATUS => self::ORDER_BY_STATUS,
            self::ORDER_OPTION_QUEUE => self::ORDER_BY_QUEUE,
        ];
    }

    /**
     * figures out a default order by direction.
     */
    protected function getDefaultDirection(): string
    {
        switch ($this->order) {
            case self::ORDER_OPTION_CREATED:
                return self::ORDER_DESCENDING;
            default:
                return self::ORDER_ASCENDING;
        }
    }

    /**
     * Calculates the query offset based on the page number and rpp.
     */
    protected function getOffset(): int
    {
        return ($this->page * $this->rpp) - $this->rpp;
    }

    /**
     * Returns the select line of the SQL query.
     */
    protected function getQuerySelect(): Builder
    {
        $qb = Download::join('packets', 'packets.id', '=', 'downloads.packet_id')
            ->join('networks', 'networks.id', 'packets.network_id')
            ->join('clients', 'clients.network_id', 'networks.id')
            ->join('instances', 'instances.client_id', 'clients.id')
            ->join('bots', 'bots.id', 'packets.bot_id');

        if ($this->getFilterLocked()) {
            $qb->join('file_download_locks', 'file_download_locks.file_name', 'downloads.file_name');
        }

        return $qb;
    }

    /**
     * Adds Filters to the Query.
     */
    protected function getQueryFilters(Builder $qb): Builder
    {
        $qb = $this->filterFileName($qb);
        $qb = $this->filterStatuses($qb);
        $qb = $this->filterInstances($qb);
        $qb = $this->filterDateRange($qb);

        return $qb;
    }

    /**
     * Adds sorting order to the Query.
     */
    protected function getQueryOrder(Builder $qb): Builder
    {
        $orderMap = $this->getOrderMap();
        $orderColumn = $orderMap[$this->getOrder()];
        $direction = $this->getDirection();

        return $qb->orderBy($orderColumn, $direction);
    }

    /**
     * Adds the limit and offset to the query.
     */
    protected function getQueryOffset(Builder $qb): Builder
    {
        return $qb->offset($this->getOffset())
            ->limit($this->getRpp());
    }

    /**
     * Adds filtering by file name to the query.
     */
    protected function filterFileName(Builder $qb): Builder
    {
        $filterFileName = $this->getFilterFileName();

        if ($filterFileName !== null) {
            $qb = $qb->where('packets.file_name', $filterFileName);
        }

        return $qb;
    }

    /**
     * Adds filtering by status to the query.
     */
    protected function filterStatuses(Builder $qb): Builder
    {
        $filterInStatuses = $this->getFilterInStatuses();
        $filterOutStatuses = $this->getFilterOutStatuses();

        if (count($filterInStatuses) > 0) {
            $qb = $qb->whereIn('downloads.status', $filterInStatuses);
        } elseif (count($filterOutStatuses) > 0) {
            $qb = $qb->whereNotIn('downloads.status', $filterOutStatuses);
        }

        return $qb;
    }

    /**
     * Adds filtering by Instances to the query.
     */
    protected function filterInstances(Builder $qb): Builder
    {
        $filterInInstances = $this->getFilterInInstances();
        $filterOutInstances = $this->getFilterOutInstances();

        if (count($filterInInstances) > 0) {
            $qb = $qb->whereIn('instances.id', $filterInInstances);
        } elseif (count($filterOutInstances) > 0) {
            $qb = $qb->whereNotIn('instances.id', $filterOutInstances);
        }

        return $qb;
    }

    /**
     * Returns a Query Builder that filters results with date.
     */
    protected function filterDateRange(Builder $qb): Builder
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        if ($start !== null) {
            $start = $start->format(self::MYSQL_TIMESTAMP_FORMAT);
        }

        if ($end !== null) {
            $end = $end->format(self::MYSQL_TIMESTAMP_FORMAT);
        }

        // If Filtering both by Start and End Dates.
        if ($start !== null && $end !== null) {
            $qb = $qb->whereBetween('downloads.updated_at', [$start, $end]);
        }

        // If Filtering Start Date Only.
        if ($start !== null && $end === null) {
            $qb = $qb->where('downloads.updated_at', '>=', $start);
        }

        // If Filtering End Date Only.
        if ($start === null && $end !== null) {
            $qb = $qb->where('downloads.updated_at', '<=', $end);
        }

        return $qb;
    }

    /**
     * Ensures the value of direction is within the list of available direction options.
     */
    protected function sanitizeDirection(string $direction): string
    {
        $direction = strtolower($direction);
        $options = self::getDirectionOptions();

        return (in_array($direction, $options)) ? $direction : $this->getDefaultDirection();
    }

    /**
     * Ensures the value of order is within the list of available order options.
     */
    protected function sanitizeOrder(string $order): string
    {
        $order = strtolower($order);
        $options = self::getOrderOptions();

        return (in_array($order, $options)) ? $order : self::ORDER_OPTION_DEFAULT;
    }

    /**
     * With a list of Status strings, only return the ones that are valid.
     */
    protected function sanitizeStatusList(array $statusList): array
    {
        $intersection = array_intersect($statusList, self::getStatusOptions());

        return $intersection;
    }

    /**
     * With a list of instance ID's filter it through a query and only return the id's that exist.
     */
    protected function sanitizeInstanceList(array $instanceList): array
    {
        return Instance::whereIn('id', $instanceList)->pluck('id')->toArray();
    }

    /**
     * Get holds the value of the order.
     */
    public function getOrder(): ?string
    {
        return $this->order;
    }

    /**
     * Set holds the value of the order.
     *
     * @param  string  $order  Holds the value of the order.
     */
    public function setOrder(string $order): void
    {
        $this->order = $this->sanitizeOrder($order);
    }

    /**
     * Get records per page.
     */
    public function getRpp(): int
    {
        return $this->rpp;
    }

    /**
     * Set records per page.
     *
     * @param  int  $rpp  Records per page.
     */
    public function setRpp(int $rpp): void
    {
        // Prevent Rpp from being a funky number.
        if ($rpp < 1) {
            throw new IllegalRppException("Records Per Page (Rpp) value: $rpp is not valid.");
        }

        $this->rpp = $rpp;
    }

    /**
     * Get page of the recordset.
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * Set page of the recordset.
     *
     * @param  int  $page  Page of the recordset.
     */
    public function setPage(int $page): void
    {
        // Prevent Page from being a funky number.
        if ($page < 1) {
            throw new IllegalPageException("Page value: $page is not valid.");
        }

        $this->page = $page;
    }

    /**
     * Get earlist DateTime of query.
     *
     * @return DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set earlist DateTime of query.
     *
     * @param  DateTime  $startDate  Earlist DateTime of query.
     */
    public function setStartDate(DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    /**
     * Get latest DateTime of query.
     *
     * @return DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set latest DateTime of query.
     *
     * @param  DateTime  $endDate  Latest DateTime of query.
     */
    public function setEndDate(DateTime $endDate): void
    {
        $this->endDate = $endDate;
    }

    /**
     * Get direction of result order
     */
    public function getDirection(): string
    {
        if ($this->direction === null) {
            return $this->getDefaultDirection();
        }

        return $this->direction;
    }

    /**
     * Set direction of result order
     *
     * @param  string  $direction  Direction of result order
     */
    public function setDirection(string $direction): void
    {
        $this->direction = $direction;
    }

    /**
     * Get list of Statuses to filter In
     *
     * @return array<string>
     */
    public function getFilterInStatuses()
    {
        return $this->filterInStatuses;
    }

    /**
     * Set list of Statuses to filter In
     *
     * @param  array<string>  $filterInStatuses  List of Statuses to filter In
     */
    public function setFilterInStatuses(array $filterInStatuses): void
    {
        $this->filterInStatuses = $this->sanitizeStatusList($filterInStatuses);
    }

    /**
     * Get list of Statuses to filter out
     *
     * @return array<string>
     */
    public function getFilterOutStatuses()
    {
        return $this->filterOutStatuses;
    }

    /**
     * Set list of Statuses to filter out
     *
     * @param  array<string>  $filterOutStatuses  List of Statuses to filter out
     */
    public function setFilterOutStatuses(array $filterOutStatuses): void
    {
        $this->filterOutStatuses = $this->sanitizeStatusList($filterOutStatuses);
    }

    /**
     * Get list of Instance IDs to filter in.
     *
     * @return array<int>
     */
    public function getFilterInInstances()
    {
        return $this->filterInInstances;
    }

    /**
     * Set list of Instance IDs to filter in.
     *
     * @param  array<int>  $filterInInstances  List of Instance IDs to filter in.
     */
    public function setFilterInInstances(array $filterInInstances): void
    {
        $this->filterInInstances = $this->sanitizeInstanceList($filterInInstances);
    }

    /**
     * Get list of Instance IDs to filter out.
     *
     * @return array<int>
     */
    public function getFilterOutInstances()
    {
        return $this->filterOutInstances;
    }

    /**
     * Set list of Instance IDs to filter out.
     *
     * @param  array<int>  $filterOutInstances  List of Instance IDs to filter out.
     */
    public function setFilterOutInstances(array $filterOutInstances): void
    {
        $this->filterOutInstances = $this->sanitizeInstanceList($filterOutInstances);
    }

    /**
     * Get name of file to filter by.
     *
     * @return string
     */
    public function getFilterFileName()
    {
        return $this->filterFileName;
    }

    /**
     * Set name of file to filter by.
     *
     * @param  string  $filterFileName  Name of file to filter by.
     */
    public function setFilterFileName(string $filterFileName): void
    {
        $this->filterFileName = $filterFileName;
    }

    /**
     * Get whether to include only files that are locked.
     */
    public function getFilterLocked(): bool
    {
        return $this->filterLocked;
    }

    /**
     * Set whether to include only files that are locked.
     *
     * @param  bool  $filterLocked  Whether to include only files that are locked.
     */
    public function setFilterLocked(bool $filterLocked): void
    {
        $this->filterLocked = $filterLocked;
    }
}
