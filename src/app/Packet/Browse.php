<?php

namespace App\Packet;

use App\Exceptions\IllegalPageException;
use App\Exceptions\IllegalRppException;
use App\Media\MediaDynamicRange;
use App\Media\MediaLanguage;
use App\Media\MediaResolution;
use App\Media\MediaType;
use App\Models\Bot;
use App\Models\Network;
use App\Packet\File\FileExtension;
use DateTime;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Browse class is a comprehensive tool for creating SQL queries for the packets table.
 */
class Browse
{
    // Date format constant
    const MYSQL_TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    // Order constants
    const ORDER_BY_PACKET_CREATED = 'p.created_at';

    const ORDER_BY_FILE_RELEASE = 'f.created_at';

    const ORDER_BY_GETS = 'p.gets';

    const ORDER_BY_FILE_NAME = 'p.file_name';

    // Order option constants
    const ORDER_OPTION_CREATED = 'created';

    const ORDER_OPTION_RELEASE = 'release';

    const ORDER_OPTION_GETS = 'gets';

    const ORDER_OPTION_NAME = 'name';

    const ORDER_OPTION_DEFAULT = self::ORDER_OPTION_CREATED;

    // Sort direction constants
    const ORDER_ASCENDING = 'asc';

    const ORDER_DESCENDING = 'desc';

    // Default pagination constants
    const DEFAULT_RPP = 40;

    const DEFAULT_PAGE = 1;

    // Filter constants
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

    // Properties for filtering lists
    /**
     * List of media types to filter in.
     */
    protected array $filterInMediaTypes = [];

    /**
     * List of media types to filter out.
     */
    protected array $filterOutMediaTypes = [];

    /**
     * List of bots to filter in.
     */
    protected array $filterInBots = [];

    /**
     * List of bots to filter out.
     */
    protected array $filterOutBots = [];

    /**
     * List of bot nicks to filter in.
     */
    protected array $filterInNicks = [];

    /**
     * List of bot nicks to filter out.
     */
    protected array $filterOutNicks = [];

    /**
     * List of languages to filter in.
     */
    protected array $filterInLanguages = [];

    /**
     * List of languages to filter out.
     */
    protected array $filterOutLanguages = [];

    /**
     * List of networks to filter in.
     */
    protected array $filterInNetworks = [];

    /**
     * List of networks to filter out.
     */
    protected array $filterOutNetworks = [];

    /**
     * List of resolutions to filter in.
     */
    protected array $filterInResolutions = [];

    /**
     * List of resolutions to filter out.
     */
    protected array $filterOutResolutions = [];

    /**
     * List of dynamic ranges to filter in.
     */
    protected array $filterInDynamicRange = [];

    /**
     * List of dynamic ranges to filter out.
     */
    protected array $filterOutDynamicRange = [];

    /**
     * List of file extensions to filter in.
     */
    protected array $filterInFileExtensions = [];

    /**
     * List of file extensions to filter out.
     */
    protected array $filterOutFileExtensions = [];

    /**
     * Search string entered by the user.
     */
    protected ?string $searchString = null;

    /**
     * Earliest DateTime of query.
     */
    protected ?DateTime $startDate = null;

    /**
     * Latest DateTime of query.
     */
    protected ?DateTime $endDate = null;

    /**
     * The field by which to order results.
     */
    protected ?string $order = null;

    /**
     * Direction of sorting (asc or desc).
     */
    protected ?string $direction = null;

    /**
     * Records per page.
     */
    protected ?int $rpp = null;

    /**
     * Page number of the result set.
     */
    protected ?int $page = null;

    /**
     * Instantiates a new Browse object with default values.
     * Sets default order, records per page (rpp), and page number.
     */
    public function __construct()
    {
        $this->order = self::ORDER_OPTION_DEFAULT;
        $this->rpp = self::DEFAULT_RPP;
        $this->page = self::DEFAULT_PAGE;
    }

    /**
     * Returns a list of available sorting directions.
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
            self::ORDER_OPTION_RELEASE,
            self::ORDER_OPTION_GETS,
            self::ORDER_OPTION_NAME,
        ];
    }

    /**
     * Checks whether the given filter is active (i.e., not empty or null).
     *
     * This method checks if the property corresponding to the filter is a non-empty array
     * or if it is not null, returning true if the filter is active, otherwise false.
     *
     * @param  string  $filter  The name of the filter property to check.
     * @return bool True if the filter is active, false otherwise.
     */
    public function isFiltering(string $filter): bool
    {
        $property = $this->$filter;

        // If the property is an array and has elements, it's considered active.
        if (is_array($property)) {
            return ! empty($property);
        }

        // If the property is not null, it's considered active.
        return $property !== null;
    }

    /**
     * Executes the query and returns the result as an array.
     *
     * This method constructs the query using the `makeQuery` method and executes it
     * using the database connection to retrieve the results. It is optimized to directly
     * return the results without unnecessary steps.
     *
     * @return array The result of the query execution as an array of records.
     */
    public function get(): array
    {
        return (array) DB::select($this->makeQuery());
    }

    /**
     * Executes the query and returns a paginated result.
     *
     * This method runs two queries: one to get the total number of items (`makeCountQuery`)
     * and another to fetch the items with pagination (`makeOffsetQuery`). The results are
     * then wrapped in a `LengthAwarePaginator` to provide easy pagination functionality.
     *
     * @param  array  $options  Additional pagination options (e.g., custom page name).
     * @return LengthAwarePaginator The paginated results.
     */
    public function paginate(array $options = []): LengthAwarePaginator
    {
        // Retrieve total number of items using a scalar query (count).
        $total = DB::scalar($this->makeCountQuery());

        // Fetch the items for the current page.
        $items = DB::select($this->makeOffsetQuery());

        return new LengthAwarePaginator($items, $total, $this->rpp, $this->page, $options);
    }

    /**
     * Builds and returns a complete SQL query string based on the search and filtering criteria.
     *
     * This method constructs the query in a step-by-step manner by appending the select,
     * from, filter, and order clauses. The final query string is then returned.
     *
     * @return string The constructed SQL query string.
     */
    public function makeQuery(): string
    {
        $queryParts = [
            $this->getQuerySelect(),
            $this->getQueryFrom(),
            $this->getQueryFilters(),
            $this->getQueryOrder(),
        ];

        // Join and return the parts as a single query string
        return implode(' ', $queryParts);
    }

    /**
     * Builds and returns a SQL query string for pagination based on the search and filtering criteria.
     *
     * This method constructs the offset-based query in a step-by-step manner by appending the select,
     * from, filter, order, and offset clauses. The final query string is then returned.
     *
     * @return string The constructed SQL offset query string.
     */
    public function makeOffsetQuery(): string
    {
        $queryParts = [
            $this->getQuerySelect(),
            $this->getQueryFrom(),
            $this->getQueryFilters(),
            $this->getQueryOrder(),
            $this->getQueryOffset(),
        ];

        // Return the query by joining the array elements into a single string
        return implode(' ', $queryParts);
    }

    /**
     * Builds and returns a SQL query string to calculate the total count of records based on the search and filtering criteria.
     *
     * This method constructs a query for counting the records, appending the necessary select,
     * from, and filter clauses. The final query string is then returned.
     *
     * @return string The constructed count query string.
     */
    public function makeCountQuery(): string
    {
        $queryParts = [
            $this->getQueryCount(),
            $this->getQueryFrom(),
            $this->getQueryFilters(),
        ];

        // Return the query by joining the array elements into a single string
        return implode(' ', $queryParts);
    }

    /**
     * Returns a string that composes all the filters for the query.
     *
     * This method consolidates all individual query filters into a single query string.
     * Each filter is added sequentially and concatenated into a final string, which will be used to refine
     * the query in the database query builder.
     *
     * @return string The query string with all applied filters.
     */
    protected function getQueryFilters(): string
    {
        // Collecting all filters in an array for efficient concatenation
        $filters = [
            $this->filterSearchString(),
            $this->filterDateRange(),
            $this->filterDynamicRange(),
            $this->filterMediaTypes(),
            $this->filterColumn('p.resolution', self::FILTER_IN_RESOLUTIONS, self::FILTER_OUT_RESOLUTIONS),
            $this->filterColumn('n.name', self::FILTER_IN_NETWORKS, self::FILTER_OUT_NETWORKS),
            $this->filterColumn('p.language', self::FILTER_IN_LANGUAGES, self::FILTER_OUT_LANGUAGES),
            $this->filterFileExtensions(),
            $this->filterColumn('b.id', self::FILTER_IN_BOTS, self::FILTER_OUT_BOTS),
            $this->filterColumn('b.nick', self::FILTER_IN_NICKS, self::FILTER_OUT_NICKS),
        ];

        // Using implode to join all filters into a single string
        return implode('', $filters);
    }

    /**
     * Calculates the offset for the SQL query based on the current page number and records per page (RPP).
     * The offset is used to determine the starting point of the result set for pagination purposes.
     *
     * The offset is calculated using the formula: (page - 1) * rpp.
     * Wrapping the result in max() so the calculated offset doesn't result in a negative value.
     *
     * @return int The calculated offset for the query.
     */
    protected function getOffset(): int
    {
        return max(0, ($this->page - 1) * $this->rpp);
    }

    /**
     * Combines two lists by adding elements from the new list to the old list,
     * but only if those elements are not already present.
     *
     * This version uses a set-based approach to optimize checking for existence
     * and avoid repeated traversals of the old list for each element in the new list.
     *
     * @param  array  $oldList  The existing list to which new elements may be added.
     * @param  array  $newList  The list of new elements to combine with the old list.
     * @return array The combined list, with no duplicates.
     */
    protected function combineList(array $oldList, array $newList): array
    {
        // Convert old list to a set (unique values only) for faster lookup
        $oldSet = array_flip($oldList);

        // Merge elements from the new list, checking if they already exist in the old set
        foreach ($newList as $el) {
            if (! isset($oldSet[$el])) {
                $oldList[] = $el;
                $oldSet[$el] = true; // Mark as seen
            }
        }

        return $oldList;
    }

    /**
     * Maps order options to their corresponding order values.
     *
     * This method returns a predefined mapping between order options (e.g.,
     * 'created', 'release', 'gets', 'name') and their respective database or query
     * order values. The mapping is static and does not depend on dynamic conditions.
     *
     * @return array The order map, where keys are order options and values are
     *               corresponding order values.
     */
    protected function getOrderMap(): array
    {
        // Use a static array for fast retrieval of order options
        return [
            self::ORDER_OPTION_CREATED => self::ORDER_BY_PACKET_CREATED,
            self::ORDER_OPTION_RELEASE => self::ORDER_BY_FILE_RELEASE,
            self::ORDER_OPTION_GETS => self::ORDER_BY_GETS,
            self::ORDER_OPTION_NAME => self::ORDER_BY_FILE_NAME,
        ];
    }

    /**
     * Determines the default order direction based on the order option.
     *
     * This method returns the default sorting direction (ascending or descending)
     * depending on the current order option.
     *
     * @return string The default sorting direction, either `ORDER_ASCENDING` or `ORDER_DESCENDING`.
     */
    protected function getDefaultDirection(): string
    {
        // Return the default direction based on the order option.
        return $this->order === self::ORDER_OPTION_NAME ? self::ORDER_ASCENDING : self::ORDER_DESCENDING;
    }

    /**
     * Generic column filtering method.
     *
     * This method applies a filter on a specified column, either by using the `IN` clause or the `NOT IN` clause,
     * depending on the filtering state. The method should be used when the column query follows a standard pattern.
     * For example, applying filters like `AND p.language IN ('german', 'korean', 'spanish')`.
     *
     * @param  string  $column  The column to apply the filter on.
     * @param  string  $filter  The filter condition to include in the query.
     * @param  string|null  $negativeFilter  The filter condition to exclude from the query, if any.
     * @return string The SQL query snippet for the specified column filter.
     */
    protected function filterColumn(string $column, string $filter, ?string $negativeFilter): string
    {
        // Check if the positive filter is active
        if ($this->isFiltering($filter)) {
            return "AND $column IN (".$this->makeListStr($this->$filter).")\n";
        }

        // Check if the negative filter is active
        if ($negativeFilter !== null && $this->isFiltering($negativeFilter)) {
            return "AND $column NOT IN (".$this->makeListStr($this->$negativeFilter).")\n";
        }

        // Return an empty string if no valid filters are found
        return '';
    }

    /**
     * Generates a SQL filter clause for a user-provided search string.
     *
     * Checks if a search string filter is applied and, if so, creates a
     * SQL `MATCH` clause to filter results based on the provided search string.
     * The query uses the `BOOLEAN MODE` to perform full-text search matching
     * on the `file_name` column.
     *
     * @return string The SQL filter clause or an empty string if no search filter is applied.
     */
    protected function filterSearchString(): string
    {
        if (! $this->isFiltering(self::FILTER_SEARCH_STRING)) {
            return '';
        }

        // Get the search string and return the formatted SQL filter clause
        $searchString = $this->getSearchString();

        return "AND MATCH (p.file_name) AGAINST ('$searchString' IN BOOLEAN MODE)\n";
    }

    /**
     * Formats a search string to match all words by adding a plus sign before each word.
     *
     * This method splits the input string into individual words and prepends a `+` sign
     * to each word, which is commonly used in full-text search queries to indicate a
     * match for all words (i.e., a logical AND).
     *
     * @param  string  $searchString  The search string to format.
     * @return string The formatted search string where each word is prefixed with a `+` symbol.
     */
    protected function formatSearchMatchAll(string $searchString): string
    {
        // Split the search string into words and add '+' to each word using array_map for efficiency
        return implode(' ', array_map(fn ($word) => "+$word", explode(' ', $searchString)));
    }

    /**
     * Returns a SQL filter clause for filtering results by a date range.
     *
     * This method constructs a SQL query fragment based on the provided date range filter.
     * It supports filtering by both start and end dates, start date only, or end date only.
     *
     * @return string The SQL filter clause for date range, or an empty string if no filtering is applied.
     */
    protected function filterDateRange(): string
    {
        // Initialize the query as an empty string
        $query = '';

        // Check if both start and end date filters are set
        if ($this->isFiltering(self::FILTER_START_DATE) && $this->isFiltering(self::FILTER_END_DATE)) {
            // Format the start and end dates and construct the BETWEEN query
            $query = sprintf(
                "AND f.created_at BETWEEN '%s' AND '%s'\n",
                $this->startDate->format(self::MYSQL_TIMESTAMP_FORMAT),
                $this->endDate->format(self::MYSQL_TIMESTAMP_FORMAT)
            );
        }
        // Check if only the start date filter is set
        elseif ($this->isFiltering(self::FILTER_START_DATE)) {
            $query = sprintf(
                "AND f.created_at >= '%s'\n",
                $this->startDate->format(self::MYSQL_TIMESTAMP_FORMAT)
            );
        }
        // Check if only the end date filter is set
        elseif ($this->isFiltering(self::FILTER_END_DATE)) {
            $query = sprintf(
                "AND f.created_at <= '%s'\n",
                $this->endDate->format(self::MYSQL_TIMESTAMP_FORMAT)
            );
        }

        return $query;
    }

    /**
     * Constructs a SQL filter clause for media types based on filtering conditions.
     *
     * This method returns a query fragment that filters results based on selected or excluded media types.
     * If media types are filtered in, it uses those directly. If media types are filtered out, it excludes them
     * from the default list of media types.
     *
     * @return string The SQL filter clause for media types, or an empty string if no filtering is applied.
     */
    protected function filterMediaTypes(): string
    {
        $query = '';
        $state = 'IN';
        $selectedMediaTypes = MediaType::getMediaTypes();

        // Check if filtering for included media types
        if ($this->isFiltering(self::FILTER_IN_MEDIA_TYPES)) {
            $selectedMediaTypes = $this->getFilterInMediaTypes();
        }
        // Check if filtering for excluded media types
        elseif ($this->isFiltering(self::FILTER_OUT_MEDIA_TYPES)) {
            $state = 'NOT IN';
            $selectedMediaTypes = array_diff($selectedMediaTypes, $this->getFilterOutMediaTypes());
        }

        // Only build the query if there are selected media types after filtering
        if (! empty($selectedMediaTypes)) {
            $listStr = $this->makeListStr($selectedMediaTypes);
            $query = "AND p.media_type $state ($listStr)\n";
        }

        return $query;
    }

    /**
     * Constructs a SQL filter clause based on dynamic range values (HDR, Dolby Vision).
     *
     * This method checks whether the dynamic range values are being filtered for inclusion or exclusion
     * and returns the corresponding SQL query fragment.
     * If HDR or Dolby Vision is included or excluded, the query is adjusted accordingly.
     *
     * @return string The SQL filter clause for dynamic range values, or an empty string if no filtering is applied.
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
        }
        // Check for exclusion of dynamic range values
        elseif ($this->isFiltering(self::FILTER_OUT_DYNAMIC_RANGE)) {
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
     * Constructs a SQL filter clause based on selected file extensions.
     *
     * This method checks if file extensions are being filtered for inclusion or exclusion.
     * It returns a corresponding SQL query fragment with optimized checks and string formatting.
     *
     * @return string The SQL filter clause for file extensions, or an empty string if no filtering is applied.
     */
    protected function filterFileExtensions(): string
    {
        // Initialize variables
        $state = 'IN';
        $selectedFileExtensions = $this->isFiltering(self::FILTER_IN_FILE_EXTENSIONS)
            ? $this->getFilterInFileExtensions()
            : FileExtension::getFileExtensions();

        // Handle exclusion of file extensions
        if ($this->isFiltering(self::FILTER_OUT_FILE_EXTENSIONS)) {
            $state = 'NOT IN';
            $selectedFileExtensions = array_diff($selectedFileExtensions, $this->getFilterOutFileExtensions());
        }

        // Generate the query only if there are selected file extensions
        if (! empty($selectedFileExtensions)) {
            $listStr = $this->makeListStr($selectedFileExtensions);

            return "AND p.extension $state ($listStr)\n";
        }

        return ''; // Return an empty string if no filtering is applied
    }

    /**
     * Constructs the FROM and JOIN components of the SQL query.
     *
     * This method returns the base query structure with the necessary table joins.
     * It conditionally adds an additional join if date filters are applied.
     *
     * @return string The SQL query fragment for the FROM clause and table joins.
     */
    protected function getQueryFrom(): string
    {
        // Initialize an array to hold parts of the query
        $queryParts = [
            'FROM mcol.packets p',
            'JOIN mcol.bots b ON p.bot_id = b.id',
            'JOIN mcol.networks n ON p.network_id = n.id',
        ];

        // Conditionally add INNER JOIN for file_first_appearances if date filters are applied
        if ($this->isFiltering(self::FILTER_START_DATE) || $this->isFiltering(self::FILTER_END_DATE)) {
            $queryParts[] = 'INNER JOIN mcol.file_first_appearances f ON p.file_name = f.file_name';
        }

        // Add the WHERE clause with a trailing space for future concatenation
        $queryParts[] = 'WHERE 1';

        // Use implode to join the query parts with a space separator
        return implode(' ', $queryParts);
    }

    /**
     * Constructs the SELECT line of the SQL query.
     *
     * This method generates the SELECT clause, listing all the fields to be retrieved
     * from the database. If date filters are applied, it includes the 'first_appearance'
     * field as well.
     *
     * @return string The SELECT clause of the SQL query.
     */
    protected function getQuerySelect(): string
    {
        // Initialize an array with the static query parts
        $queryParts = [
            'SELECT p.id',
            'p.created_at',
            'p.updated_at',
            'p.language',
            'p.gets',
            'p.size',
            'p.media_type',
            'p.file_name',
            'p.extension',
            'p.resolution',
            'p.is_hdr',
            'p.is_dolby_vision',
            'p.number',
            'p.meta',
            'b.id as bot_id',
            'b.nick',
            'n.name as network',
        ];

        // Conditionally add 'first_appearance' field if date filters are applied
        if ($this->isFiltering(self::FILTER_START_DATE) || $this->isFiltering(self::FILTER_END_DATE)) {
            $queryParts[] = 'f.created_at as first_appearance';
        }

        // Join the query parts with a comma separator and add a newline at the end
        return implode(', ', $queryParts);
    }

    /**
     * Constructs the COUNT line of the SQL query.
     *
     * Generates the SELECT clause for counting the total number of rows.
     *
     * @return string The SELECT COUNT query part.
     */
    protected function getQueryCount(): string
    {
        return 'SELECT count(*)';
    }

    /**
     * Constructs the ORDER BY clause for the SQL query.
     *
     * Generates the ORDER BY part of the SQL query, determining the column
     * to order by and the direction (ASC or DESC).
     *
     * @return string The ORDER BY clause of the SQL query.
     */
    protected function getQueryOrder(): string
    {
        // Retrieve the order column based on the current order setting
        $orderColumn = $this->getOrderMap()[$this->order] ?? 'p.created_at';  // Default to 'p.created_at' if not found
        $direction = $this->getDirection();  // Get the order direction (ASC or DESC)

        return "ORDER BY $orderColumn $direction";
    }

    /**
     * Constructs the LIMIT and OFFSET clause for the SQL query.
     *
     * Generates the LIMIT and OFFSET part of the SQL query. The LIMIT
     * defines the maximum number of records to return, and the OFFSET determines
     * the starting point for the query..
     *
     * @return string The LIMIT and OFFSET clause of the SQL query.
     */
    protected function getQueryOffset(): string
    {
        return sprintf('LIMIT %d OFFSET %d', $this->getRpp(), $this->getOffset());
    }

    /**
     * Expands the Media Language List.
     *
     * Some languages are represented in multiple ways. The expanded language list
     * adds all the ways that each language is represented by looking up
     * language mappings from a predefined set of expanded languages.
     *
     * @param  array  $mediaLanguageList  A list of media languages to be expanded.
     * @return array The expanded list of media languages.
     */
    protected function expandMediaLanguageList(array $mediaLanguageList): array
    {
        // Retrieve the expanded language mappings once to avoid unnecessary calls
        $expanded = MediaLanguage::getExpandedLanguages();

        // Initialize an empty array to hold the final expanded list
        $expandedMediaLanguageList = [];

        // Loop through the media language list and merge expanded languages where applicable
        foreach ($mediaLanguageList as $language) {
            if (isset($expanded[$language])) {
                // Merge the expanded languages efficiently
                $expandedMediaLanguageList = array_merge($expandedMediaLanguageList, $expanded[$language]);
            } else {
                // If no expansion exists for the language, add it directly
                $expandedMediaLanguageList[] = $language;
            }
        }

        return $expandedMediaLanguageList;
    }

    /**
     * Expands the Dynamic Range List.
     * Some dynamic ranges are represented in multiple ways.
     * This method expands each dynamic range in the list by adding all possible representations.
     *
     * @param  array  $mediaDynamicRangeList  The list of dynamic ranges to expand.
     * @return array The expanded list of dynamic ranges, including all their representations.
     */
    protected function expandMediaDynamicRangeList(array $mediaDynamicRangeList): array
    {
        $expanded = MediaDynamicRange::getExpandedDynamicRanges();
        $expandedMediaDynamicRangeList = [];

        // Collect all expanded dynamic ranges
        foreach ($mediaDynamicRangeList as $dynamicRange) {
            if (isset($expanded[$dynamicRange])) {
                $expandedMediaDynamicRangeList = array_merge($expandedMediaDynamicRangeList, $expanded[$dynamicRange]);
            } else {
                // Include the dynamic range itself if no expansion is needed
                $expandedMediaDynamicRangeList[] = $dynamicRange;
            }
        }

        return $expandedMediaDynamicRangeList;
    }

    /**
     * Ensures the value of direction is within the list of available direction options.
     * If the direction is valid, it returns the sanitized value. Otherwise, it returns the default direction.
     *
     * @param  string  $direction  The direction value to be sanitized.
     * @return string The sanitized direction value, either the input direction or the default.
     */
    protected function sanitizeDirection(string $direction): string
    {
        return in_array($normalizedDirection = strtolower($direction), self::getDirectionOptions(), true)
            ? $normalizedDirection
            : $this->getDefaultDirection();
    }

    /**
     * Ensures the value of order is within the list of available order options.
     * If the order is valid, it returns the sanitized value. Otherwise, it returns the default order option.
     *
     * @param  string  $order  The order value to be sanitized.
     * @return string The sanitized order value, either the input order or the default.
     */
    protected function sanitizeOrder(string $order): string
    {
        return in_array($normalizedOrder = strtolower($order), self::getOrderOptions(), true)
            ? $normalizedOrder
            : self::ORDER_OPTION_DEFAULT;
    }

    /**
     * Filters a list of bot IDs through a query and returns only the IDs that exist in the database.
     * Ensures that only valid bot IDs are included in the result by querying the database.
     *
     * @param  array  $botList  An array of bot IDs to be filtered.
     * @return array An array of existing bot IDs found in the database.
     */
    protected function sanitizeBotList(array $botList): array
    {
        // Ensure botList is not empty before performing the query to avoid unnecessary database access
        return empty($botList) ? [] : Bot::whereIn('id', $botList)->pluck('id')->toArray();
    }

    /**
     * Filters a list of file extension IDs through a query and returns only the ones that exist.
     * Checks the provided list of file extension IDs against a pre-existing list of valid file extensions.
     * Only the valid file extensions are returned.
     *
     * @param  array  $fileExtensionList  An array of file extension IDs to be filtered.
     * @return array An array containing only the valid file extension IDs that exist.
     */
    protected function sanitizeFileExtensionList(array $fileExtensionList): array
    {
        if (empty($fileExtensionList)) {
            return [];
        }

        $fileExtensions = FileExtension::getFileExtensions();

        // Return only the file extensions that exist in both the valid list and the provided list
        return array_intersect($fileExtensionList, $fileExtensions);
    }

    /**
     * Filters a list of media dynamic range strings and returns only the valid ones.
     * Checks the provided list of dynamic ranges against a pre-existing list of valid dynamic ranges.
     * Only the valid dynamic range strings are returned.
     *
     * @param  array  $mediaDynamicRangeList  An array of media dynamic range strings to be filtered.
     * @return array An array containing only the valid dynamic range strings that exist.
     */
    protected function sanitizeMediaDynamicRangeList(array $mediaDynamicRangeList): array
    {
        if (empty($mediaDynamicRangeList)) {
            return [];
        }

        $validDynamicRanges = MediaDynamicRange::getMediaDynamicRanges();

        // Return only the dynamic ranges that exist in both the provided list and the valid dynamic range list
        return array_intersect($mediaDynamicRangeList, $validDynamicRanges);
    }

    /**
     * Filters a list of media language strings and returns only the valid ones.
     * This method checks the provided list of media languages against a pre-existing list of valid languages.
     * Only the valid media language strings are returned.
     *
     * @param  array  $mediaLanguageList  An array of media language strings to be filtered.
     * @return array An array containing only the valid media language strings that exist.
     */
    protected function sanitizeMediaLanguageList(array $mediaLanguageList): array
    {
        if (empty($mediaLanguageList)) {
            return [];
        }

        $validLanguages = MediaLanguage::getMediaLanguages();

        // Return only the media languages that exist in both the provided list and the valid media language list
        return array_intersect($mediaLanguageList, $validLanguages);
    }

    /**
     * Checks the provided list of network names against the database.
     * Only the names that exist in the database are returned.
     *
     * @param  array  $networkList  An array of network names to be filtered.
     * @return array An array containing only the network names that exist in the database.
     */
    protected function sanitizeNetworkList(array $networkList): array
    {
        // Ensure networkList is not empty before performing the query to avoid unnecessary database access
        return empty($networkList) ? [] : Network::whereIn('name', $networkList)->pluck('name')->toArray();
    }

    /**
     * Filters a list of media resolution strings and returns only the valid ones.
     *
     * @param  array  $mediaResolutionList  An array of media resolution strings to be filtered.
     * @return array An array containing only the valid media resolution strings.
     */
    protected function sanitizeMediaResolutionList(array $mediaResolutionList): array
    {
        if (empty($mediaResolutionList)) {
            return [];
        }

        // Perform an intersection with valid resolutions and return the result
        return array_intersect($mediaResolutionList, MediaResolution::getMediaResolutions());
    }

    /**
     * Filters a list of media type strings and returns only the valid ones.
     *
     * @param  array  $mediaTypeList  An array of media type strings to be filtered.
     * @return array An array containing only the valid media type strings.
     */
    protected function sanitizeMediaTypeList(array $mediaTypeList): array
    {
        if (empty($mediaTypeList)) {
            return [];
        }

        // Perform an intersection with valid media types and return the result
        return array_intersect($mediaTypeList, MediaType::getMediaTypes());
    }

    /**
     * Converts an array of values into a string formatted for use in a SQL "WHERE IN" clause.
     * The values in the list are enclosed in single quotes and separated by commas.
     *
     * @param  array  $list  An array of values to be formatted into a string.
     * @return string A string of values suitable for inclusion in a SQL "WHERE IN" clause.
     */
    protected function makeListStr(array $list): string
    {
        if (empty($list)) {
            return '';
        }

        return '\''.implode('\',\'', $list).'\'';
    }

    /**
     * Get the value of filterInBots
     */
    public function getFilterInBots(): array
    {
        return $this->filterInBots;
    }

    /**
     * Set the value of filterInBots
     */
    public function setFilterInBots(array $filterInBots): void
    {
        $this->filterInBots = $this->sanitizeBotList($filterInBots);
    }

    /**
     * Get the value of filterOutBots
     */
    public function getFilterOutBots(): array
    {
        return $this->filterOutBots;
    }

    /**
     * Set the value of filterOutBots
     */
    public function setFilterOutBots(array $filterOutBots): void
    {
        $this->filterOutBots = $this->sanitizeBotList($filterOutBots);
    }

    /**
     * Get the value of filterInLanguages
     */
    public function getFilterInLanguages(): array
    {
        return $this->filterInLanguages;
    }

    /**
     * Set the value of filterInLanguages
     */
    public function setFilterInLanguages(array $filterInLanguages): void
    {
        $sanitizedLanguages = $this->sanitizeMediaLanguageList($filterInLanguages);
        $expandedLanguages = $this->expandMediaLanguageList($sanitizedLanguages);
        $this->filterInLanguages = $expandedLanguages;
    }

    /**
     * Get the value of filterOutLanguages
     */
    public function getFilterOutLanguages(): array
    {
        return $this->filterOutLanguages;
    }

    /**
     * Set the value of filterOutLanguages
     */
    public function setFilterOutLanguages(array $filterOutLanguages): void
    {
        $sanitizedLanguages = $this->sanitizeMediaLanguageList($filterOutLanguages);
        $expandedLanguages = $this->expandMediaLanguageList($sanitizedLanguages);
        $this->filterOutLanguages = $expandedLanguages;
    }

    /**
     * Get the value of filterInResolutions
     */
    public function getFilterInResolutions(): array
    {
        return $this->filterInResolutions;
    }

    /**
     * Set the value of filterInResolutions
     */
    public function setFilterInResolutions(array $filterInResolutions): void
    {
        $this->filterInResolutions = $this->sanitizeMediaResolutionList($filterInResolutions);
    }

    /**
     * Get the value of filterOutResolutions
     */
    public function getFilterOutResolutions(): array
    {
        return $this->filterOutResolutions;
    }

    /**
     * Set the value of filterOutResolutions
     */
    public function setFilterOutResolutions(array $filterOutResolutions): void
    {
        $this->filterOutResolutions = $this->sanitizeMediaResolutionList($filterOutResolutions);
    }

    /**
     * Get the value of filterInNetworks
     */
    public function getFilterInNetworks(): array
    {
        return $this->filterInNetworks;
    }

    /**
     * Set the value of filterInNetworks
     */
    public function setFilterInNetworks(array $filterInNetworks): void
    {
        $this->filterInNetworks = $this->sanitizeNetworkList($filterInNetworks);
    }

    /**
     * Get the value of filterOutNetworks
     */
    public function getFilterOutNetworks(): array
    {
        return $this->filterOutNetworks;
    }

    /**
     * Set the value of filterOutNetworks
     */
    public function setFilterOutNetworks(array $filterOutNetworks): void
    {
        $this->filterOutNetworks = $this->sanitizeNetworkList($filterOutNetworks);
    }

    /**
     * Get the value of filterInDynamicRange
     */
    public function getFilterInDynamicRange(): array
    {
        return $this->filterInDynamicRange;
    }

    /**
     * Set the value of filterInDynamicRange
     */
    public function setFilterInDynamicRange(array $filterInDynamicRange): void
    {
        $sanitizedDynamicRanges = $this->sanitizeMediaDynamicRangeList($filterInDynamicRange);
        $expandedDynamicRanges = $this->expandMediaDynamicRangeList($sanitizedDynamicRanges);
        $this->filterInDynamicRange = $expandedDynamicRanges;
    }

    /**
     * Get the value of filterOutDynamicRange
     */
    public function getFilterOutDynamicRange(): array
    {
        return $this->filterOutDynamicRange;
    }

    /**
     * Set the value of filterOutDynamicRange
     */
    public function setFilterOutDynamicRange(array $filterOutDynamicRange): void
    {
        $sanitizedDynamicRanges = $this->sanitizeMediaDynamicRangeList($filterOutDynamicRange);
        $expandedDynamicRanges = $this->expandMediaDynamicRangeList($sanitizedDynamicRanges);
        $this->filterOutDynamicRange = $expandedDynamicRanges;
    }

    /**
     * Get the value of filterInFileExtensions
     */
    public function getFilterInFileExtensions(): array
    {
        return $this->filterInFileExtensions;
    }

    /**
     * Set the value of filterInFileExtensions
     */
    public function setFilterInFileExtensions(array $filterInFileExtensions): void
    {
        $this->filterInFileExtensions = $this->sanitizeFileExtensionList($filterInFileExtensions);
    }

    /**
     * Get the value of filterOutFileExtensions
     */
    public function getFilterOutFileExtensions(): array
    {
        return $this->filterOutFileExtensions;
    }

    /**
     * Set the value of filterOutFileExtensions
     */
    public function setFilterOutFileExtensions(array $filterOutFileExtensions): void
    {
        $this->filterOutFileExtensions = $this->sanitizeFileExtensionList($filterOutFileExtensions);
    }

    /**
     * Get the value of filterInMediaTypes
     */
    public function getFilterInMediaTypes(): array
    {
        return $this->filterInMediaTypes;
    }

    /**
     * Set the value of filterInMediaTypes
     */
    public function setFilterInMediaTypes(array $filterInMediaTypes): void
    {
        $this->filterInMediaTypes = $this->sanitizeMediaTypeList($filterInMediaTypes);
    }

    /**
     * Get the value of filterOutMediaTypes
     */
    public function getFilterOutMediaTypes(): array
    {
        return $this->filterOutMediaTypes;
    }

    /**
     * Set the value of filterOutMediaTypes
     */
    public function setFilterOutMediaTypes(array $filterOutMediaTypes): void
    {
        $this->filterOutMediaTypes = $this->sanitizeMediaTypeList($filterOutMediaTypes);
    }

    /**
     * Get the value of filterInNicks
     */
    public function getFilterInNicks(): array
    {
        return $this->filterInNicks;
    }

    /**
     * Set the value of filterInNicks
     */
    public function setFilterInNicks(array $filterInNicks): self
    {
        $this->filterInNicks = $filterInNicks;

        return $this;
    }

    /**
     * Get the value of filterOutNicks
     */
    public function getFilterOutNicks(): array
    {
        return $this->filterOutNicks;
    }

    /**
     * Set the value of filterOutNicks
     */
    public function setFilterOutNicks(array $filterOutNicks): self
    {
        $this->filterOutNicks = $filterOutNicks;

        return $this;
    }

    /**
     * Get the value of searchString
     */
    public function getSearchString(): ?string
    {
        return $this->searchString;
    }

    /**
     * Set the value of searchString
     */
    public function setSearchString(?string $searchString): void
    {
        $this->searchString = $searchString;
    }

    /**
     * Get the earliest DateTime of query.
     */
    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }

    /**
     * Set the earliest DateTime of query.
     */
    public function setStartDate(?DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    /**
     * Get the latest DateTime of query.
     */
    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }

    /**
     * Set the latest DateTime of query.
     */
    public function setEndDate(?DateTime $endDate): void
    {
        $this->endDate = $endDate;
    }

    /**
     * Get the direction of result order.
     */
    public function getDirection(): ?string
    {
        return $this->direction ?? $this->getDefaultDirection();
    }

    /**
     * Set the direction of result order.
     */
    public function setDirection(?string $direction): void
    {
        $this->direction = $direction;
    }

    /**
     * Get the value of the order.
     */
    public function getOrder(): ?string
    {
        return $this->order;
    }

    /**
     * Set the value of the order.
     */
    public function setOrder(?string $order): void
    {
        $this->order = $this->sanitizeOrder($order);
    }

    /**
     * Get records per page.
     */
    public function getRpp(): ?int
    {
        return $this->rpp;
    }

    /**
     * Set records per page.
     *
     * @throws IllegalRppException
     */
    public function setRpp(?int $rpp): void
    {
        if ($rpp !== null && $rpp < 1) {
            throw new IllegalRppException("Records Per Page (Rpp) value: $rpp is not valid.");
        }
        $this->rpp = $rpp;
    }

    /**
     * Get page of the recordset.
     */
    public function getPage(): ?int
    {
        return $this->page;
    }

    /**
     * Set page of the recordset.
     *
     * @throws IllegalPageException
     */
    public function setPage(?int $page): void
    {
        if ($page !== null && $page < 1) {
            throw new IllegalPageException("Page value: $page is not valid.");
        }
        $this->page = $page;
    }
}
