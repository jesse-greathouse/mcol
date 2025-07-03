<?php

namespace App\Packet;

use DateTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DownloadQueueRequestHandler
{
    const PAGE_KEY = 'page';

    const RPP_KEY = 'rpp';

    const ORDER_KEY = 'order';

    const DIRECTION_KEY = 'direction';

    const START_DATE_KEY = 'start_date';

    const END_DATE_KEY = 'end_date';

    const FILE_NAME_KEY = 'file_name';

    const LOCKED_KEY = 'locked';

    const IN_INSTANCES_KEY = 'in_instances';

    const OUT_INSTANCES_KEY = 'out_instances';

    const IN_STATUSES_KEY = 'in_status';

    const OUT_STATUSES_KEY = 'out_status';

    /**
     * A Web Request
     */
    protected Request $request;

    /**
     * A DownloadQueue object
     */
    protected DownloadQueue $downloadQueue;

    /**
     * Instansiates a DownloadQueue object.
     * DownloadQueue is a comprehensive tool for querying for the downloads table.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->downloadQueue = new DownloadQueue;
        $this->handleInput();
    }

    /**
     * Returns an array of all available filters and their current values.
     */
    public function getFilters(): array
    {
        return [
            self::PAGE_KEY => $this->downloadQueue->getPage(),
            self::RPP_KEY => $this->downloadQueue->getRpp(),
            self::ORDER_KEY => $this->downloadQueue->getOrder(),
            self::DIRECTION_KEY => $this->downloadQueue->getDirection(),
            self::LOCKED_KEY => $this->downloadQueue->getFilterLocked(),
            self::START_DATE_KEY => $this->downloadQueue->getStartDate(),
            self::END_DATE_KEY => $this->downloadQueue->getEndDate(),
            self::FILE_NAME_KEY => $this->downloadQueue->getFilterFileName(),
            self::IN_INSTANCES_KEY => $this->downloadQueue->getFilterInInstances(),
            self::OUT_INSTANCES_KEY => $this->downloadQueue->getFilterOutInstances(),
            self::IN_STATUSES_KEY => $this->downloadQueue->getFilterInStatuses(),
            self::OUT_STATUSES_KEY => $this->downloadQueue->getFilterOutStatuses(),
        ];
    }

    /**
     * Configures the downloadQueue options from given inputs.
     */
    public function handleInput(): void
    {
        $this->page();
        $this->rpp();
        $this->order();
        $this->direction();
        $this->locked();
        $this->fileName();
        $this->startDate();
        $this->endDate();
        $this->instances();
        $this->statuses();
    }

    /**
     * Runs the query on the downloadQueue object and returns the result set.
     */
    public function get(): Collection
    {
        return $this->downloadQueue->get();
    }

    /**
     * Runs the query on the downloadQueue queue method.
     */
    public function queue(): array
    {
        return $this->downloadQueue->queue();
    }

    /**
     * Runs a paginated query.
     */
    public function paginate(array $options = []): LengthAwarePaginator
    {
        return $this->downloadQueue->paginate($options);
    }

    /**
     * Handle Page input.
     */
    protected function page(): void
    {
        if ($this->request->has(self::PAGE_KEY) && $this->request->input(self::PAGE_KEY) !== null) {
            $this->downloadQueue->setPage($this->request->input(self::PAGE_KEY));
        }
    }

    /**
     * Handle Rpp input.
     */
    protected function rpp(): void
    {
        if ($this->request->has(self::RPP_KEY) && $this->request->input(self::RPP_KEY) !== null) {
            $this->downloadQueue->setRpp($this->request->input(self::RPP_KEY));
        }
    }

    /**
     * Handle Order input.
     */
    protected function order(): void
    {
        if ($this->request->has(self::ORDER_KEY) && $this->request->input(self::ORDER_KEY) !== null) {
            $order = strtolower($this->request->input(self::ORDER_KEY));
            if (in_array($order, DownloadQueue::getOrderOptions())) {
                $this->downloadQueue->setOrder($order);
            }
        }
    }

    /**
     * Handle direction input.
     */
    protected function direction(): void
    {
        if ($this->request->has(self::DIRECTION_KEY) && $this->request->input(self::DIRECTION_KEY) !== null) {
            $direction = strtolower($this->request->input(self::DIRECTION_KEY));
            if (in_array($direction, DownloadQueue::getDirectionOptions())) {
                $this->downloadQueue->setDirection($direction);
            }
        }
    }

    /**
     * Handle file name input.
     */
    protected function fileName(): void
    {
        if ($this->request->has(self::FILE_NAME_KEY) && $this->request->input(self::FILE_NAME_KEY) !== null) {
            $this->downloadQueue->setFilterFileName($this->request->input(self::FILE_NAME_KEY));
        }
    }

    /**
     * Handle locked input.
     */
    protected function locked(): void
    {
        if ($this->request->has(self::LOCKED_KEY) && $this->request->input(self::LOCKED_KEY) !== null) {
            $sanitized = filter_var($this->request->input(self::LOCKED_KEY), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($sanitized !== null) {
                $this->downloadQueue->setFilterLocked($sanitized);
            }
        }
    }

    /**
     * DateTime to start the chronology of the results
     */
    protected function startDate(): void
    {
        if ($this->request->has(self::START_DATE_KEY) && $this->request->input(self::START_DATE_KEY) !== null) {
            $dateStr = $this->request->input(self::START_DATE_KEY);
            $startDate = new DateTime($dateStr);
            $this->downloadQueue->setStartDate($startDate);
        }
    }

    /**
     * DateTime to end the chronology of the results
     */
    protected function endDate(): void
    {
        if ($this->request->has(self::END_DATE_KEY) && $this->request->input(self::END_DATE_KEY) !== null) {
            $dateStr = $this->request->input(self::END_DATE_KEY);

            // If no time is given, increment by one day.
            if (! $this->containsTimeString($dateStr)) {
                $endDate = new DateTime("$dateStr +1 day");
            } else {
                $endDate = new DateTime($dateStr);
            }

            $this->downloadQueue->setEndDate($endDate);
        }
    }

    /**
     * Handle Bots input.
     */
    protected function statuses(): void
    {
        if ($this->request->has(self::IN_STATUSES_KEY)) {
            $this->downloadQueue->setFilterInStatuses($this->request->input(self::IN_STATUSES_KEY));
        } elseif ($this->request->has(self::OUT_STATUSES_KEY)) {
            $this->downloadQueue->setFilterOutStatuses($this->request->input(self::OUT_STATUSES_KEY));
        }
    }

    /**
     * Handle Bots input.
     */
    protected function instances(): void
    {
        if ($this->request->has(self::IN_INSTANCES_KEY)) {
            $this->downloadQueue->setFilterInInstances($this->request->input(self::IN_INSTANCES_KEY));
        } elseif ($this->request->has(self::OUT_INSTANCES_KEY)) {
            $this->downloadQueue->setFilterOutInstances($this->request->input(self::OUT_INSTANCES_KEY));
        }
    }

    /**
     * Checks a date format string to see if it includes time.
     */
    protected function containsTimeString(string $dateStr): bool
    {
        $matches = [];
        preg_match('/\d+\:\d+(\:\d+)?/', $dateStr, $matches);

        return count($matches) > 0;
    }
}
