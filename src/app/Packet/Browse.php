<?php
namespace App\Packet;

use Illuminate\Pagination\LengthAwarePaginator,
    Illuminate\Support\Facades\DB;

use App\Exceptions\IllegalPageException,
    App\Exceptions\IllegalRppException;

use App\Media\MediaDynamicRange,
    App\Media\MediaLanguage,
    App\Media\MediaResolution,
    App\Media\MediaType;

use App\Models\Bot,
    App\Models\Network,
    App\Packet\File\FileExtension;

use \DateTime;

class Browse
{
    const MYSQL_TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    const ORDER_BY_PACKET_CREATED = 'p.created_at';
    const ORDER_BY_FILE_RELEASE = 'f.created_at';
    const ORDER_BY_GETS = 'p.gets';
    const ORDER_BY_FILE_NAME = 'p.file_name';

    const ORDER_OPTION_CREATED = 'created';
    const ORDER_OPTION_RELEASE = 'release';
    const ORDER_OPTION_GETS = 'gets';
    const ORDER_OPTION_NAME = 'name';
    const ORDER_OPTION_DEFAULT = self::ORDER_OPTION_CREATED;

    const ORDER_ASCENDING = 'asc';
    const ORDER_DESCENDING = 'desc';

    const DEFAULT_RPP = 40;
    const DEFAULT_PAGE = 1;

    const FILTER_END_DATE = 'endDate';
    const FILTER_START_DATE = 'startDate';
    const FILTER_SEARCH_STRING = 'searchString';
    const FILTER_IN_BOTS = 'filterInBots';
    const FILTER_OUT_BOTS = 'filterOutBots';
    const FILTER_IN_NICKS = 'filterInNicks';
    const FILTER_OUT_NICKS = 'filterOutNicks';
    const FILTER_IN_NETWORKS = 'filterInNetworks';
    const FILTER_OUT_NETWORKS = 'filterOutNetworks';
    const FILTER_IN_LANGUAGES = 'filterInLanguages';
    const FILTER_OUT_LANGUAGES = 'filterOutLanguages';
    const FILTER_IN_MEDIA_TYPES = 'filterInMediaTypes';
    const FILTER_OUT_MEDIA_TYPES = 'filterOutMediaTypes';
    const FILTER_IN_RESOLUTIONS = 'filterInResolutions';
    const FILTER_OUT_RESOLUTIONS = 'filterOutResolutions';
    const FILTER_IN_DYNAMIC_RANGE = 'filterInDynamicRange';
    const FILTER_OUT_DYNAMIC_RANGE = 'filterOutDynamicRange';
    const FILTER_IN_FILE_EXTENSIONS = 'filterInFileExtensions';
    const FILTER_OUT_FILE_EXTENSIONS = 'filterOutFileExtensions';

    // Lists of media types to filter in or out.
    /**
     * @var array
     */
    protected array $filterInMediaTypes = [];

    /**
     * @var array
     */
    protected array $filterOutMediaTypes = [];

    // Lists of bots to filter in or out.
    /**
     * @var array
     */
    protected array $filterInBots = [];

    /**
     * @var array
     */
    protected array $filterOutBots = [];

    // Mask of a bot nick to filter in or out.
    /**
     * @var array
     */
    protected array $filterInNicks = [];

    /**
     * @var array
     */
    protected array $filterOutNicks = [];

    // Lists of languages to filter in or out.
    /**
     * @var array
     */
    protected array $filterInLanguages = [];

    /**
     * @var array
     */
    protected array $filterOutLanguages = [];

    // Lists of networks to filter in or out.
    /**
     * @var array
     */
    protected array $filterInNetworks = [];

    /**
     * @var array
     */
    protected array $filterOutNetworks = [];

    // Lists of resolutions to filter in or out.
    /**
     * @var array
     */
    protected array $filterInResolutions = [];

    /**
     * @var array
     */
    protected array $filterOutResolutions = [];

    // Lists of dynamic ranges to filter in or out.
    /**
     * @var array
     */
    protected array $filterInDynamicRange = [];

    /**
     * @var array
     */
    protected array $filterOutDynamicRange = [];

    // Lists of file extensions to filter in or out.
    /**
     * @var array
     */
    protected array $filterInFileExtensions = [];

    /**
     * @var array
     */
    protected array $filterOutFileExtensions = [];

    /**
     * String entered for search.
     *
     * @var string
     */
    protected $searchString;

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
     * Instansiates a Browse object.
     * Browse is a comprehensive tool for creating SQL queries for the packets table.
     */
    public function __construct()
    {
        $this->order = self::ORDER_OPTION_DEFAULT;
        $this->rpp = self::DEFAULT_RPP;
        $this->page = self::DEFAULT_PAGE;
    }

    /**
     * Returns a list of available order directions.
     *
     * @return array
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
     *
     * @return array
     */
    public static function getOrderOptions(): array
    {
        return [
            self::ORDER_OPTION_CREATED,
            self::ORDER_OPTION_RELEASE,
            self::ORDER_OPTION_GETS,
            self::ORDER_OPTION_NAME,
        ];
    }

    /**
     * A shortcut function to make downstream logic a little bit more terse.
     * There is a lot of this checking happening in a lot of these methods.
     * This should enable it to be easier to read.
     *
     * @param string
     * @return bool
     */
    public function isFiltering(string $filter): bool
    {
        if ('array' === gettype($this->$filter)) {
            return (0 < count($this->$filter)) ? true : false;
        }

        if (null !== $this->$filter) return true;

        return false;
    }

    /**
     * Run the Query and return the result.
     *
     * @return array
     */
    public function get(): array
    {
        return DB::select($this->makeQuery());
    }

    /**
     * Run the Query and return paginated results.
     *
     * @return LengthAwarePaginator
     */
    public function paginate(array $options = []): LengthAwarePaginator
    {
        $total = DB::scalar($this->makeCountQuery());
        $items = DB::select($this->makeOffsetQuery());
        $paginator = new LengthAwarePaginator($items, $total, $this->rpp, $this->page, $options);
        return $paginator;
    }

    /**
     * Creates a query based on all the search and filtering criteria.
     *
     * @return string
     */
    public function makeQuery(): string
    {
        $query = $this->getQuerySelect();
        $query .= $this->getQueryFrom();
        $query .= $this->getQueryFilters();
        $query .= $this->getQueryOrder();

        return $query;
    }

    /**
     * Creates a query based on all the search and filtering criteria.
     *
     * @return string
     */
    public function makeOffsetQuery(): string
    {
        $query = $this->getQuerySelect();
        $query .= $this->getQueryFrom();
        $query .= $this->getQueryFilters();
        $query .= $this->getQueryOrder();
        $query .= $this->getQueryOffset();

        return $query;
    }

    /**
     * Creates a query that calcuates the maximum number of records.
     *
     * @return string
     */
    public function makeCountQuery(): string
    {
        $query = $this->getQueryCount();
        $query .= $this->getQueryFrom();
        $query .= $this->getQueryFilters();

        return $query;
    }

    /**
     * Returns a string that composes all the filters for the query.
     *
     * @return string
     */
    protected function getQueryFilters(): string {
        $query = $this->filterSearchString();
        $query .= $this->filterDateRange();
        $query .= $this->filterDynamicRange();
        $query .= $this->filterMediaTypes();
        $query .= $this->filterColumn('p.resolution', self::FILTER_IN_RESOLUTIONS, self::FILTER_OUT_RESOLUTIONS);
        $query .= $this->filterColumn('n.name', self::FILTER_IN_NETWORKS, self::FILTER_OUT_NETWORKS);
        $query .= $this->filterColumn('p.language', self::FILTER_IN_LANGUAGES, self::FILTER_OUT_LANGUAGES);
        $query .= $this->filterFileExtensions();
        $query .= $this->filterColumn('b.id', self::FILTER_IN_BOTS, self::FILTER_OUT_BOTS);
        $query .= $this->filterColumn('b.nick', self::FILTER_IN_NICKS, self::FILTER_OUT_NICKS);

        return $query;
    }

    /**
     * Calculates the query offset based on the page number and rpp.
     *
     * @return integer
     */
    protected function getOffset(): int
    {
        return ($this->page * $this->rpp) - $this->rpp;
    }

    /**
     * Combines lists where a value does not already exist.
     *
     * @param array $oldList
     * @param array $newList
     * @return array
     */
    protected function combineList(array $oldList, array $newList): array {
        foreach($newList as $el) {
            if (!in_array($el, $oldList)) {
                $oldList[] = $el;
            }
        }

        return $oldList;
    }

    /**
     * Maps order options to order values
     *
     * @return array
     */
    protected function getOrderMap(): array
    {
        return [
            self::ORDER_OPTION_CREATED  => self::ORDER_BY_PACKET_CREATED,
            self::ORDER_OPTION_RELEASE  => self::ORDER_BY_FILE_RELEASE,
            self::ORDER_OPTION_GETS     => self::ORDER_BY_GETS,
            self::ORDER_OPTION_NAME     => self::ORDER_BY_FILE_NAME,
        ];
    }

    /**
     * figures out a default order by direction.
     *
     * @return string
     */
    protected function getDefaultDirection(): string
    {
        switch($this->order) {
            case self::ORDER_OPTION_NAME:
                return self::ORDER_ASCENDING;
            default:
                return self::ORDER_DESCENDING;
        }
    }

    /**
     * Generic column filtering method.
     * Use this only when the column query fits the common pattern.
     * e.g.: "AND p.language IN ('german,','korean','spanish')";
     *
     * @param string $column
     * @param string $filter
     * @param string $negativeFilter
     * @return string
     */
    protected function filterColumn(string $column, string $filter, string $negativeFilter = null): string
    {
        $query = '';
        $state = 'IN';
        $list  = null;

        if ($this->isFiltering($filter)) {
            $list = $this->makeListStr($this->$filter);
        } else if (null !== $negativeFilter &&  $this->isFiltering($negativeFilter)) {
            $list = $this->makeListStr($this->$negativeFilter);
            $state = 'NOT IN';
        }

        if (null !== $list) {
            $query = "AND $column $state ($list)\n";
        }

        return $query;
    }

    /**
     * Creates a SQL filter clause for a user provided search string.
     *
     * @return string
     */
    protected function filterSearchString(): string
    {
        $query = '';

        if ($this->isFiltering(self::FILTER_SEARCH_STRING)) {
            $searchString = $this->getSearchString();
            $query = "AND MATCH (p.file_name) AGAINST ('$searchString' IN BOOLEAN MODE)\n";
        }

        return $query;
    }

    /**
     * Formats a search string to match all words.
     *
     * @param string $searchString
     * @return string
     */
    protected function formatSearchMatchAll(string $searchString): string
    {
        $formatted = [];
        $words = explode(' ', $searchString);

        foreach($words as $word) {
            $formatted[] = "+$word";
        }

        return implode(' ', $formatted);
    }

    /**
     * Returns a string that filters results with date.
     *
     * @return string
     */
    protected function filterDateRange(): string
    {
        $query = '';
        $start = null;
        $end = null;

        if ($this->isFiltering(self::FILTER_START_DATE)) {
            $start = $this->startDate->format(self::MYSQL_TIMESTAMP_FORMAT);
        }

        if ($this->isFiltering(self::FILTER_END_DATE)) {
            $end = $this->endDate->format(self::MYSQL_TIMESTAMP_FORMAT);
        }

        // If Filtering both by Start and End Dates.
        if (null !== $start && null !== $end) {
            $query = "AND (f.created_at BETWEEN '$start' AND '$end')\n";
        }

        // If Filtering Start Date Only.
        if (null !== $start && null === $end) {
            $query = "AND f.created_at >= '$start'\n";
        }

        // If Filtering End Date Only.
        if (null === $start && null !== $end) {
            $query = "AND f.created_at <= '$end'\n";
        }

        return $query;
    }

    /**
     * Returns a string that only includes certain media types.
     *
     * @return string
     */
    protected function filterMediaTypes(): string
    {
        $query = '';
        $state = 'IN';
        $selectedMediaTypes = MediaType::getMediaTypes();

        if ($this->isFiltering(self::FILTER_IN_MEDIA_TYPES)) {
            $selectedMediaTypes = $this->getFilterInMediaTypes();
        } else if ($this->isFiltering(self::FILTER_OUT_MEDIA_TYPES)) {
            $state = 'NOT IN';
            $selectedMediaTypes = array_diff($selectedMediaTypes, $this->getFilterOutMediaTypes());
        }

        if (0 < count($selectedMediaTypes)) {
            $listStr = $this->makeListStr($selectedMediaTypes);
            $query = "AND p.media_type $state ($listStr)\n";
        }

        return $query;
    }

    /**
     * Returns a string to only include certain dynamic range values.
     *
     * @return string
     */
    protected function filterDynamicRange(): string
    {
        $query = '';

        if ($this->isFiltering(self::FILTER_IN_DYNAMIC_RANGE)) {
            $filterInDynamicRange = $this->getFilterInDynamicRange();
            if (in_array(MediaDynamicRange::HDR, $filterInDynamicRange)) {
                $query .= "AND p.is_hdr = 1\n";
            }

            if (in_array(MediaDynamicRange::DOLBY_VISION, $filterInDynamicRange)) {
                $query .= "AND p.is_dolby_vision = 1\n";
            }
        } else if ($this->isFiltering(self::FILTER_OUT_DYNAMIC_RANGE)) {
            $filterOutDynamicRange = $this->getFilterOutDynamicRange();
            if (in_array(MediaDynamicRange::HDR, $filterOutDynamicRange)) {
                $query .= "AND p.is_hdr = 0\n";
            }

            if (in_array(MediaDynamicRange::DOLBY_VISION, $filterOutDynamicRange)) {
                $query .= "AND p.is_dolby_vision = 0\n";
            }
        }

        return $query;
    }

    /**
     * Returns a string to only include certain dynamic range values.
     *
     * @return string
     */
    protected function filterFileExtensions(): string
    {
        $query = '';
        $state = 'IN';
        $selectedFileExtensions = FileExtension::getFileExtensions();

        if ($this->isFiltering(self::FILTER_IN_FILE_EXTENSIONS)) {
            $selectedFileExtensions = $this->getFilterInFileExtensions();
        } else if ($this->isFiltering(self::FILTER_OUT_FILE_EXTENSIONS)) {
            $state = 'NOT IN';
            $selectedFileExtensions = array_diff($selectedFileExtensions, $this->getFilterOutFileExtensions());
        }

        if (0 < count($selectedFileExtensions)) {
            $listStr = $this->makeListStr($selectedFileExtensions);
            $query = "AND p.extension $state ($listStr)\n";
        }

        return $query;
    }

    /**
     * Returns the component of the query that does the From and Joins of the tables.
     *
     * @return string
     */
    protected function getQueryFrom(): string
    {
        $query = 'FROM mcol.packets p' . "\n";
        $query .= 'JOIN mcol.bots b on p.bot_id = b.id' . "\n";
        $query .= 'JOIN mcol.networks n on p.network_id = n.id' . "\n";

        if ($this->isFiltering(self::FILTER_START_DATE) || $this->isFiltering(self::FILTER_END_DATE)) {
            $query .= 'INNER JOIN mcol.file_first_appearances f on p.file_name = f.file_name' . "\n";
        }

        $query .= 'WHERE 1' . "\n";

        return $query;
    }

    /**
     * Returns the select line of the SQL query.
     *
     * @return string
     */
    protected function getQuerySelect(): string
    {
        $query = 'SELECT p.id';
        $query .= ', p.created_at';
        $query .= ', p.updated_at';
        $query .= ', p.language';
        $query .= ', p.gets';
        $query .= ', p.size';
        $query .= ', p.media_type';
        $query .= ', p.file_name';
        $query .= ', p.extension';
        $query .= ', p.resolution';
        $query .= ', p.is_hdr';
        $query .= ', p.is_dolby_vision';
        $query .= ', p.number';
        $query .= ', p.meta';
        $query .= ', b.id as bot_id';
        $query .= ', b.nick';
        $query .= ', n.name as network';

        if ($this->isFiltering(self::FILTER_START_DATE) || $this->isFiltering(self::FILTER_END_DATE)) {
            $query .= ', f.created_at as first_appearance';
        }

        $query .= "\n";

        return $query;
    }

    /**
     * Returns the select line of the SQL query.
     *
     * @return string
     */
    protected function getQueryCount(): string
    {
        return "SELECT count(*)\n";
    }

    /**
     * Returns the order line of the SQL query.
     *
     * @return string
     */
    protected function getQueryOrder(): string
    {
        $orderMap = $this->getOrderMap();
        $orderColumn = $orderMap[$this->order];
        $direction = $this->getDirection();
        return "ORDER BY $orderColumn $direction ";
    }

    /**
     * Returns the limit and offset line of the SQL query.
     *
     * @return string
     */
    protected function getQueryOffset(): string
    {
        $offset = $this->getOffset();
        $limit = $this->getRpp();
        return "LIMIT $limit offset $offset";
    }

    /**
     * Expands the Media Language List
     * Some languages are represented in multiple ways.
     * The expanded language list adds all the ways that each language is represented.
     *
     * @param array $mediaLanguageList
     * @return array
     */
    protected function expandMediaLanguageList(array $mediaLanguageList): array
    {
        $expanded = MediaLanguage::getExpandedLanguages();
        $expandedMediaLanguageList = $mediaLanguageList;

        foreach($mediaLanguageList as $language) {
            if (isset($expanded[$language])) {
                $expandedMediaLanguageList = array_merge($expandedMediaLanguageList, $expanded[$language]);
            }
        }

        return $expandedMediaLanguageList;
    }

    /**
     * Expands the Dynamic Range List
     * Some dynamic ranges are represented in multiple ways.
     * The expanded dynamic range list adds all the ways that each dynamic range is represented.
     *
     * @param array $mediaDynamicRangeList
     * @return array
     */
    protected function expandMediaDynamicRangeList(array $mediaDynamicRangeList): array
    {
        $expanded = MediaDynamicRange::getExpandedDynamicRanges();
        $expandedMediaDynamicRangeList = $mediaDynamicRangeList;

        foreach($mediaDynamicRangeList as $dynamicRange) {
            if (isset($expanded[$dynamicRange])) {
                $expandedMediaDynamicRangeList = array_merge($expandedMediaDynamicRangeList, $expanded[$dynamicRange]);
            }
        }

        return $expandedMediaDynamicRangeList;
    }

    /**
     * Ensures the value of direction is within the list of available direction options.
     *
     * @param $direction
     * @return string
     */
    protected function sanitizeDirection(string $direction): string
    {
        $direction = strtolower($direction);
        $options = self::getDirectionOptions();
        return (in_array($direction, $options)) ? $direction : $this->getDefaultDirection();
    }

    /**
     * Ensures the value of order is within the list of available order options.
     *
     * @param $order
     * @return string
     */
    protected function sanitizeOrder(string $order): string
    {
        $order = strtolower($order);
        $options = self::getOrderOptions();
        return (in_array($order, $options)) ? $order : self::ORDER_OPTION_DEFAULT;
    }

    /**
     * With a list of bot ID's filter it through a query and only return the id's that exist.
     *
     * @param array $botList
     * @return array
     */
    protected function sanitizeBotList(array $botList): array
    {
        return Bot::whereIn('id', $botList)->pluck('id')->toArray();
    }

    /**
     * With a list of File Extension ID's filter it through a query and only return the id's that exist.
     *
     * @param array $fileExtensionList
     * @return array
     */
    protected function sanitizeFileExtensionList(array $fileExtensionList): array
    {
        $fileExtensions = FileExtension::getFileExtensions();
        return array_intersect($fileExtensions, $fileExtensionList);
    }

    /**
     * With a list of Dynamic Range strings, only return the ones that are valid.
     *
     * @param array $mediaDynamicRangeList
     * @return array
     */
    protected function sanitizeMediaDynamicRangeList(array $mediaDynamicRangeList): array
    {
        $intersection = array_intersect($mediaDynamicRangeList, MediaDynamicRange::getMediaDynamicRanges());
        return $intersection;
    }

    /**
     * With a list of Media Language strings, only return the ones that are valid.
     *
     * @param array $mediaLanguageList
     * @return array
     */
    protected function sanitizeMediaLanguageList(array $mediaLanguageList): array
    {
        $intersection = array_intersect($mediaLanguageList, MediaLanguage::getMediaLanguages());
        return $intersection;
    }

    /**
     * With a list of network ID's filter it through a query and only return the id's that exist.
     *
     * @param array $networkList
     * @return array
     */
    protected function sanitizeNetworkList(array $networkList): array
    {
        return Network::whereIn('name', $networkList)->pluck('name')->toArray();
    }

    /**
     * With a list of Media Resolution strings, only return the ones that are valid.
     *
     * @param array $mediaResolutionList
     * @return array
     */
    protected function sanitizeMediaResolutionList(array $mediaResolutionList): array
    {
        $intersection = array_intersect($mediaResolutionList, MediaResolution::getMediaResolutions());
        return $intersection;
    }

    /**
     * With a list of Media Type strings, only return the ones that are valid.
     *
     * @param array $mediaTypeList
     * @return array
     */
    protected function sanitizeMediaTypeList(array $mediaTypeList): array
    {
        $intersection = array_intersect($mediaTypeList, MediaType::getMediaTypes());
        return $intersection;
    }

    /**
     * Turns an array list into a string that is useable in a SQL: "WHERE IN" clause.
     *
     * @param array $list
     * @return string
     */
    protected function makeListStr(array $list): string
    {
        return '\'' . implode('\',\'', $list) . '\'';
    }

    /**
     * Get the value of filterInBots
     *
     * @return  array
     */
    public function getFilterInBots()
    {
        return $this->filterInBots;
    }

    /**
     * Set the value of filterInBots
     *
     * @param  array  $filterInBots
     *
     * @return  void
     */
    public function setFilterInBots(array $filterInBots): void
    {
        $this->filterInBots = $this->sanitizeBotList($filterInBots);
    }

    /**
     * Get the value of filterOutBots
     *
     * @return  array
     */
    public function getFilterOutBots()
    {
        return $this->filterOutBots;
    }

    /**
     * Set the value of filterOutBots
     *
     * @param  array  $filterOutBots
     *
     * @return  void
     */
    public function setFilterOutBots(array $filterOutBots): void
    {
        $this->filterOutBots = $this->sanitizeBotList($filterOutBots);
    }

    /**
     * Get the value of filterInLanguages
     *
     * @return  array
     */
    public function getFilterInLanguages()
    {
        return $this->filterInLanguages;
    }

    /**
     * Set the value of filterInLanguages
     *
     * @param  array  $filterInLanguages
     *
     * @return  void
     */
    public function setFilterInLanguages(array $filterInLanguages): void
    {
        $sanitizedLanguages = $this->sanitizeMediaLanguageList($filterInLanguages);
        $expandedLanguages = $this->expandMediaLanguageList($sanitizedLanguages);
        $this->filterInLanguages = $expandedLanguages;
    }

    /**
     * Get the value of filterOutLanguages
     *
     * @return  array
     */
    public function getFilterOutLanguages()
    {
        return $this->filterOutLanguages;
    }

    /**
     * Set the value of filterOutLanguages
     *
     * @param  array  $filterOutLanguages
     *
     * @return  void
     */
    public function setFilterOutLanguages(array $filterOutLanguages): void
    {
        $sanitizedLanguages = $this->sanitizeMediaLanguageList($filterOutLanguages);
        $expandedLanguages = $this->expandMediaLanguageList($sanitizedLanguages);
        $this->filterOutLanguages = $expandedLanguages;
    }

    /**
     * Get the value of filterInResolutions
     *
     * @return  array
     */
    public function getFilterInResolutions()
    {
        return $this->filterInResolutions;
    }

    /**
     * Set the value of filterInResolutions
     *
     * @param  array  $filterInResolutions
     *
     * @return  void
     */
    public function setFilterInResolutions(array $filterInResolutions): void
    {
        $this->filterInResolutions = $this->sanitizeMediaResolutionList($filterInResolutions);
    }

    /**
     * Get the value of filterOutResolutions
     *
     * @return  array
     */
    public function getFilterOutResolutions()
    {
        return $this->filterOutResolutions;
    }

    /**
     * Set the value of filterOutResolutions
     *
     * @param  array  $filterOutResolutions
     *
     * @return  void
     */
    public function setFilterOutResolutions(array $filterOutResolutions): void
    {
        $this->filterOutResolutions = $this->sanitizeMediaResolutionList($filterOutResolutions);
    }

    /**
     * Get the value of filterInNetworks
     *
     * @return  array
     */
    public function getFilterInNetworks()
    {
        return $this->filterInNetworks;
    }

    /**
     * Set the value of filterInNetworks
     *
     * @param  array  $filterInNetworks
     *
     * @return  void
     */
    public function setFilterInNetworks(array $filterInNetworks): void
    {
        $this->filterInNetworks = $this->sanitizeNetworkList($filterInNetworks);
    }

    /**
     * Get the value of filterOutNetworks
     *
     * @return  array
     */
    public function getFilterOutNetworks()
    {
        return $this->filterOutNetworks;
    }

    /**
     * Set the value of filterOutNetworks
     *
     * @param  array  $filterOutNetworks
     *
     * @return  void
     */
    public function setFilterOutNetworks(array $filterOutNetworks): void
    {
        $this->filterOutNetworks = $this->sanitizeNetworkList($filterOutNetworks);
    }

    /**
     * Get the value of filterInDynamicRange
     *
     * @return  array
     */
    public function getFilterInDynamicRange()
    {
        return $this->filterInDynamicRange;
    }

    /**
     * Set the value of filterInDynamicRange
     *
     * @param  array  $filterInDynamicRange
     *
     * @return  void
     */
    public function setFilterInDynamicRange(array $filterInDynamicRange): void
    {
        $sanitizedDynamicRanges = $this->sanitizeMediaDynamicRangeList($filterInDynamicRange);
        $expandedDynamicRanges = $this->expandMediaDynamicRangeList($sanitizedDynamicRanges);
        $this->filterInDynamicRange = $expandedDynamicRanges;

    }

    /**
     * Get the value of filterOutDynamicRange
     *
     * @return  array
     */
    public function getFilterOutDynamicRange()
    {
        return $this->filterOutDynamicRange;
    }

    /**
     * Set the value of filterOutDynamicRange
     *
     * @param  array  $filterOutDynamicRange
     *
     * @return  void
     */
    public function setFilterOutDynamicRange(array $filterOutDynamicRange): void
    {
        $sanitizedDynamicRanges = $this->sanitizeMediaDynamicRangeList($filterOutDynamicRange);
        $expandedDynamicRanges = $this->expandMediaDynamicRangeList($sanitizedDynamicRanges);
        $this->filterOutDynamicRange = $expandedDynamicRanges;
    }

    /**
     * Get the value of filterInFileExtensions
     *
     * @return  array
     */
    public function getFilterInFileExtensions()
    {
        return $this->filterInFileExtensions;
    }

    /**
     * Set the value of filterInFileExtensions
     *
     * @param  array  $filterInFileExtensions
     *
     * @return  void
     */
    public function setFilterInFileExtensions(array $filterInFileExtensions): void
    {
        $this->filterInFileExtensions = $this->sanitizeFileExtensionList($filterInFileExtensions);
    }

    /**
     * Get the value of filterOutFileExtensions
     *
     * @return  array
     */
    public function getFilterOutFileExtensions()
    {
        return $this->filterOutFileExtensions;
    }

    /**
     * Set the value of filterOutFileExtensions
     *
     * @param  array  $filterOutFileExtensions
     *
     * @return  void
     */
    public function setFilterOutFileExtensions(array $filterOutFileExtensions): void
    {
        $this->filterOutFileExtensions = $this->sanitizeFileExtensionList($filterOutFileExtensions);
    }

    /**
     * Get the value of searchString
     *
     * @return  string|null
     */
    public function getSearchString(): string|null
    {
        return $this->searchString;
    }

    /**
     * Set the value of searchString
     *
     * @param  string  $searchString
     *
     * @return  void
     */
    public function setSearchString(string $searchString): void
    {
        $this->searchString = $searchString;
    }

    /**
     * Get earlist DateTime of query.
     *
     * @return  DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set earlist DateTime of query.
     *
     * @param  DateTime  $startDate  Earlist DateTime of query.
     *
     * @return  void
     */
    public function setStartDate(DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    /**
     * Get latest DateTime of query.
     *
     * @return  DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set latest DateTime of query.
     *
     * @param  DateTime  $endDate  Latest DateTime of query.
     *
     * @return  void
     */
    public function setEndDate(DateTime $endDate):void
    {
        $this->endDate = $endDate;
    }

    /**
     * Get direction of result order
     *
     * @return  string
     */
    public function getDirection(): string
    {
        if (null === $this->direction) {
            return $this->getDefaultDirection();
        }

        return $this->direction;
    }

    /**
     * Set direction of result order
     *
     * @param  string  $direction  Direction of result order
     *
     * @return  void
     */
    public function setDirection(string $direction):void
    {
        $this->direction = $direction;
    }

    /**
     * Get the value of filterInMediaTypes
     *
     * @return  array
     */
    public function getFilterInMediaTypes()
    {
        return $this->filterInMediaTypes;
    }

    /**
     * Set the value of filterInMediaTypes
     *
     * @param  array  $filterInMediaTypes
     *
     * @return  void
     */
    public function setFilterInMediaTypes(array $filterInMediaTypes): void
    {
        $this->filterInMediaTypes = $this->sanitizeMediaTypeList($filterInMediaTypes);
    }

    /**
     * Get the value of filterOutMediaTypes
     *
     * @return  array
     */
    public function getFilterOutMediaTypes()
    {
        return $this->filterOutMediaTypes;
    }

    /**
     * Set the value of filterOutMediaTypes
     *
     * @param  array  $filterOutMediaTypes
     *
     * @return  void
     */
    public function setFilterOutMediaTypes(array $filterOutMediaTypes): void
    {
        $this->filterOutMediaTypes = $this->sanitizeMediaTypeList($filterOutMediaTypes);
    }

    /**
     * Get holds the value of the order.
     *
     * @return  string
     */
    public function getOrder(): string|null
    {
        return $this->order;
    }

    /**
     * Set holds the value of the order.
     *
     * @param  string  $order  Holds the value of the order.
     *
     * @return  void
     */
    public function setOrder(string $order): void
    {
        $this->order = $this->sanitizeOrder($order);
    }

    /**
     * Get records per page.
     *
     * @return  int
     */
    public function getRpp(): int
    {
        return $this->rpp;
    }

    /**
     * Set records per page.
     *
     * @param  int  $rpp  Records per page.
     *
     * @return  void
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
     *
     * @return  int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * Set page of the recordset.
     *
     * @param  int  $page  Page of the recordset.
     *
     * @return  void
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
     * Get the value of filterInNicks
     *
     * @return  array
     */
    public function getFilterInNicks(): array
    {
        return $this->filterInNicks;
    }

    /**
     * Set the value of filterInNicks
     *
     * @param  array  $filterInNicks
     *
     * @return  self
     */
    public function setFilterInNicks(array $filterInNicks): void
    {
        $this->filterInNicks = $filterInNicks;
    }

    /**
     * Get the value of filterOutNicks
     *
     * @return  array
     */
    public function getFilterOutNicks(): array
    {
        return $this->filterOutNicks;
    }

    /**
     * Set the value of filterOutNicks
     *
     * @param  array  $filterOutNicks
     *
     * @return  self
     */
    public function setFilterOutNicks(array $filterOutNicks): void
    {
        $this->filterOutNicks = $filterOutNicks;
    }
}
