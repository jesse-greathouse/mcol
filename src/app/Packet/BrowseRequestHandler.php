<?php
namespace App\Packet;

use Illuminate\Http\Request,
    Illuminate\Pagination\LengthAwarePaginator;

use DateTime;

/**
 * BrowseRequestHandler is responsible for processing and handling incoming web request parameters
 * related to browsing and filtering search results. The primary purpose of this class is to act as
 * a mediator between the HTTP request layer and the database query layer, ensuring a clean separation
 * of concerns.
 *
 * The BrowseRequestHandler interprets the request input, such as page numbers, filters, and other options,
 * and configures the `Browse` object accordingly. While this class is aware of both HTTP request
 * handling and database filtering, it ensures that the concerns of the Request are kept separate,
 * allowing the database-related logic to remain isolated within the `Browse` class.
 *
 * By using this class, the application can easily handle web requests without mixing database query
 * logic into the HTTP layer, promoting maintainability and clarity in the codebase.
 *
 */
class BrowseRequestHandler
{

    // Pagination and sorting parameters
    const PAGE_KEY = 'page'; // Represents the current page in pagination
    const RPP_KEY = 'rpp'; // Number of results per page
    const ORDER_KEY = 'order'; // Specifies the field by which to order results
    const DIRECTION_KEY = 'direction'; // Defines the sorting direction (ascending/descending)

    // Date filtering parameters
    const START_DATE_KEY = 'start_date'; // Start date for filtering results
    const END_DATE_KEY = 'end_date'; // End date for filtering results

    // Bot-specific filtering parameters
    const IN_BOTS_KEY = 'in_bots'; // List of incoming bots to filter by
    const OUT_BOTS_KEY = 'out_bots'; // List of outgoing bots to filter by

    // Nickname-specific filtering parameters
    const IN_NICK_KEY = 'in_nick'; // List of incoming nicks to filter by
    const OUT_NICK_KEY = 'out_nick'; // List of outgoing nicks to filter by

    // Network-specific filtering parameters
    const IN_NETWORK_KEY = 'in_network'; // List of incoming networks to filter by
    const OUT_NETWORK_KEY = 'out_network'; // List of outgoing networks to filter by

    // Language-specific filtering parameters
    const IN_LANGUAGE_KEY = 'in_language'; // List of incoming languages to filter by
    const OUT_LANGUAGE_KEY = 'out_language'; // List of outgoing languages to filter by

    // Search string parameter
    const SEARCH_STRING_KEY = 'search_string'; // The search string for querying data

    // Media type-specific filtering parameters
    const IN_MEDIA_TYPE_KEY = 'in_media_type'; // List of incoming media types to filter by
    const OUT_MEDIA_TYPE_KEY = 'out_media_type'; // List of outgoing media types to filter by

    // Resolution-specific filtering parameters
    const IN_RESOLUTIONS_KEY = 'in_resolution'; // List of incoming resolutions to filter by
    const OUT_RESOLUTIONS_KEY = 'out_resolution'; // List of outgoing resolutions to filter by

    // Dynamic range-specific filtering parameters
    const IN_DYNAMIC_RANGE_KEY = 'in_dynamic_range'; // List of incoming dynamic ranges to filter by
    const OUT_DYNAMIC_RANGE_KEY = 'out_dynamic_range'; // List of outgoing dynamic ranges to filter by

    // File extension-specific filtering parameters
    const IN_FILE_EXTENSION_KEY = 'in_file_extension'; // List of incoming file extensions to filter by
    const OUT_FILE_EXTENSION_KEY = 'out_file_extension'; // List of outgoing file extensions to filter by

    /**
     * A Web Request instance.
     *
     * @var Request
     */
    protected Request $request;

    /**
     * A Browse instance.
     *
     * @var Browse
     */
    protected Browse $browse;

    /**
     * Constructor to initialize the request and browse objects.
     *
     * @param Request $request The request instance.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->browse = new Browse();
        $this->handleInput();
    }

    /**
     * Returns an array of all available filters and their current values.
     *
     * @return array
     */
    public function getFilters(): array
    {
        return [
            self::PAGE_KEY => $this->browse->getPage(),
            self::RPP_KEY => $this->browse->getRpp(),
            self::ORDER_KEY => $this->browse->getOrder(),
            self::DIRECTION_KEY => $this->browse->getDirection(),
            self::START_DATE_KEY => $this->browse->getStartDate(),
            self::END_DATE_KEY => $this->browse->getEndDate(),
            self::IN_BOTS_KEY => $this->browse->getFilterInBots(),
            self::OUT_BOTS_KEY => $this->browse->getFilterOutBots(),
            self::IN_NICK_KEY => $this->browse->getFilterInNicks(),
            self::OUT_NICK_KEY => $this->browse->getFilterOutNicks(),
            self::IN_NETWORK_KEY => $this->browse->getFilterInNetworks(),
            self::OUT_NETWORK_KEY => $this->browse->getFilterOutNetworks(),
            self::IN_LANGUAGE_KEY => $this->browse->getFilterInLanguages(),
            self::OUT_LANGUAGE_KEY => $this->browse->getFilterOutLanguages(),
            self::SEARCH_STRING_KEY => $this->browse->getSearchString(),
            self::IN_MEDIA_TYPE_KEY => $this->browse->getFilterInMediaTypes(),
            self::OUT_MEDIA_TYPE_KEY => $this->browse->getFilterOutMediaTypes(),
            self::IN_RESOLUTIONS_KEY => $this->browse->getFilterInResolutions(),
            self::OUT_RESOLUTIONS_KEY => $this->browse->getFilterOutResolutions(),
            self::IN_DYNAMIC_RANGE_KEY => $this->browse->getFilterInDynamicRange(),
            self::OUT_DYNAMIC_RANGE_KEY => $this->browse->getFilterOutDynamicRange(),
            self::IN_FILE_EXTENSION_KEY => $this->browse->getFilterInFileExtensions(),
            self::OUT_FILE_EXTENSION_KEY => $this->browse->getFilterOutFileExtensions(),
        ];
    }

    /**
     * Configures the browse options from given inputs.
     *
     * @return void
     */
    public function handleInput(): void
    {
        $this->page();
        $this->rpp();
        $this->order();
        $this->direction();
        $this->search();
        $this->startDate();
        $this->endDate();
        $this->bots();
        $this->nicks();
        $this->networks();
        $this->mediaTypes();
        $this->languages();
        $this->fileExtensions();
        $this->resolutions();
        $this->dynamicRanges();
    }

    /**
     * Runs the query on the browse object and returns the result set.
     *
     * @return array
     */
    public function get(): array
    {
        return $this->browse->get();
    }

    /**
     * Runs a paginated query.
     *
     * @return LengthAwarePaginator
     */
    public function paginate(array $options = []): LengthAwarePaginator
    {
        return $this->browse->paginate($options);
    }

    /**
     * Handle Page input.
     *
     * @return void
     */
    protected function page(): void
    {
        if ($page = $this->request->input(self::PAGE_KEY)) {
            $this->browse->setPage($page);
        }
    }

    /**
     * Handle Rpp input.
     *
     * @return void
     */
    protected function rpp(): void
    {
        if ($rpp = $this->request->input(self::RPP_KEY)) {
            $this->browse->setRpp($rpp);
        }
    }

    /**
     * Handle Order input.
     *
     * @return void
     */
    protected function order(): void
    {
        if ($order = strtolower($this->request->input(self::ORDER_KEY))) {
            if (in_array($order, Browse::getOrderOptions())) {
                $this->browse->setOrder($order);
            }
        }
    }

    /**
     * Handle Direction input.
     *
     * @return void
     */
    protected function direction(): void
    {
        if ($direction = strtolower($this->request->input(self::DIRECTION_KEY))) {
            if (in_array($direction, Browse::getDirectionOptions())) {
                $this->browse->setDirection($direction);
            }
        }
    }

    /**
     * Handle Search String input.
     *
     * @return void
     */
    protected function search(): void
    {
        if ($searchString = $this->request->input(self::SEARCH_STRING_KEY)) {
            $this->browse->setSearchString($searchString);
        }
    }

    /**
     * DateTime to start the chronology of the results
     *
     * @return void
     */
    protected function startDate(): void
    {
        if ($dateStr = $this->request->input(self::START_DATE_KEY)) {
            $this->browse->setStartDate(new DateTime($dateStr));
        }
    }

    /**
     * DateTime to end the chronology of the results
     *
     * @return void
     */
    protected function endDate(): void
    {
        if ($dateStr = $this->request->input(self::END_DATE_KEY)) {
            // If no time is given, increment by one day.
            $endDate = $this->containsTimeString($dateStr)
                ? new DateTime($dateStr)
                : new DateTime("$dateStr +1 day");

            $this->browse->setEndDate($endDate);
        }
    }

    /**
     * Handle Bots input.
     *
     * @return void
     */
    protected function bots(): void
    {
        if ($inBots = $this->request->input(self::IN_BOTS_KEY)) {
            $this->browse->setFilterInBots($inBots);
        } elseif ($outBots = $this->request->input(self::OUT_BOTS_KEY)) {
            $this->browse->setFilterOutBots($outBots);
        }
    }

    /**
     * Handle Nicks input.
     *
     * @return void
     */
    protected function nicks(): void
    {
        if ($inNicks = $this->request->input(self::IN_NICK_KEY)) {
            $this->browse->setFilterInNicks($inNicks);
        } elseif ($outNicks = $this->request->input(self::OUT_NICK_KEY)) {
            $this->browse->setFilterOutNicks($outNicks);
        }
    }

    /**
     * Handle Networks input.
     *
     * @return void
     */
    protected function networks(): void
    {
        if ($inNetworks = $this->request->input(self::IN_NETWORK_KEY)) {
            $this->browse->setFilterInNetworks($inNetworks);
        } elseif ($outNetworks = $this->request->input(self::OUT_NETWORK_KEY)) {
            $this->browse->setFilterOutNetworks($outNetworks);
        }
    }

    /**
     * Handle Media Types input.
     *
     * @return void
     */
    protected function mediaTypes(): void
    {
        if ($inMediaTypes = $this->request->input(self::IN_MEDIA_TYPE_KEY)) {
            $this->browse->setFilterInMediaTypes($inMediaTypes);
        } elseif ($outMediaTypes = $this->request->input(self::OUT_MEDIA_TYPE_KEY)) {
            $this->browse->setFilterOutMediaTypes($outMediaTypes);
        }
    }

    /**
     * Handle Languages input.
     *
     * @return void
     */
    protected function languages(): void
    {
        if ($inLanguages = $this->request->input(self::IN_LANGUAGE_KEY)) {
            $this->browse->setFilterInLanguages($inLanguages);
        } elseif ($outLanguages = $this->request->input(self::OUT_LANGUAGE_KEY)) {
            $this->browse->setFilterOutLanguages($outLanguages);
        }
    }

    /**
     * Handle File Extensions input.
     *
     * @return void
     */
    protected function fileExtensions(): void
    {
        if ($inFileExtensions = $this->request->input(self::IN_FILE_EXTENSION_KEY)) {
            $this->browse->setFilterInFileExtensions($inFileExtensions);
        } elseif ($outFileExtensions = $this->request->input(self::OUT_FILE_EXTENSION_KEY)) {
            $this->browse->setFilterOutFileExtensions($outFileExtensions);
        }
    }

    /**
     * Handle Resolutions input.
     *
     * @return void
     */
    protected function resolutions(): void
    {
        if ($inResolutions = $this->request->input(self::IN_RESOLUTIONS_KEY)) {
            $this->browse->setFilterInResolutions($inResolutions);
        } elseif ($outResolutions = $this->request->input(self::OUT_RESOLUTIONS_KEY)) {
            $this->browse->setFilterOutResolutions($outResolutions);
        }
    }

    /**
     * Handle Dynamic Range input.
     *
     * @return void
     */
    protected function dynamicRanges(): void
    {
        if ($inDynamicRange = $this->request->input(self::IN_DYNAMIC_RANGE_KEY)) {
            $this->browse->setFilterInDynamicRange($inDynamicRange);
        } elseif ($outDynamicRange = $this->request->input(self::OUT_DYNAMIC_RANGE_KEY)) {
            $this->browse->setFilterOutDynamicRange($outDynamicRange);
        }
    }

    /**
     * Checks a date format string to see if it includes time.
     *
     * @param string $dateStr
     * @return bool
     */
    protected function containsTimeString(string $dateStr): bool
    {
        // Match any string containing a time format (HH:MM or HH:MM:SS)
        return preg_match('/\d{1,2}:\d{2}(:\d{2})?/', $dateStr) === 1;
    }

}
