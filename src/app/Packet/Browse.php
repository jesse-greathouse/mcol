<?php
namespace App\Packet;

use App\Media\MediaDynamicRange,
    App\Media\MediaLanguage,
    App\Media\MediaResolution,
    App\Media\MediaType;

use App\Models\Bot,
    App\Models\FileExtension;

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

    const ORDER_ASCENDING = 'ASC';
    const ORDER_DESCENDING = 'DESC';

    // Lists of media types to filter in our out.
    /**
     * @var array
     */
    protected array $filterInMediaTypes = [];

    /**
     * @var array
     */
    protected array $filterOutMediaTypes = [];

    // Lists of bots to filter in our out.
    /**
     * @var array
     */
    protected array $filterInBots = [];

    /**
     * @var array
     */
    protected array $filterOutBots = [];

    // Lists of languages to filter in our out.
    /**
     * @var array
     */
    protected array $filterInLanguages = [];
    
    /**
     * @var array
     */
    protected array $filterOutLanguages = [];

    // Lists of resolutions to filter in our out.
    /**
     * @var array
     */
    protected array $filterInResolutions = [];

    /**
     * @var array
     */
    protected array $filterOutResolutions = [];

    // Lists of dynamic ranges to filter in our out.
    /**
     * @var array
     */
    protected array $filterInDynamicRange = [];

    /**
     * @var array
     */
    protected array $filterOutDynamicRange = [];

    // Lists of file extensions to filter in our out.
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
    protected DateTime $startDate;

    /**
     * Latest DateTime of query.
     *
     * @var DateTime
     */
    protected DateTime $endDate;

    /**
     * Holds the value of the order.
     *
     * @var string
     */
    protected string $order;

    /**
     * Direction of result order
     * 
     * @var string
     */
    protected string $direction;

    /**
     * Instansiates a Browse object.
     * Browse is a comprehensive tool for creating SQL queries for the packets table.
     */
    public function __construct()
    {
        $this->order = self::ORDER_OPTION_DEFAULT;
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
     * Creates a query based on all the search and filtering criteria.
     *
     * @return string
     */
    public function makeQuery(): string
    {
        $query = $this->getQuerySelect();
        $query .= $this->getQueryFrom();
        $query .= $this->filterEmptyBotNames();
        $query .= $this->filterBots();
        $query .= $this->filterLanguages();
        $query .= $this->filterMediaTypes();
        $query .= $this->filterResolutions();
        $query .= $this->filterDynamicRange();
        $query .= $this->filterFileExtensions();
        $query .= $this->filterDateRange();
        $query .= $this->filterSearchString();
        $query .= $this->getQueryOrder();

        return $query;
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
     * Undocumented function
     *
     * @return string
     */
    protected function filterSearchString(): string
    {
        $query = '';
        $searchStr = $this->getSearchString();

        if (null !== $searchStr) {
            $query = "AND MATCH (p.file_name) AGAINST ('$searchStr' IN NATURAL LANGUAGE MODE)\n";
        }

        return $query;
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

        if (null !== $this->startDate) {
            $start = $this->startDate->format(self::MYSQL_TIMESTAMP_FORMAT);
        }

        if (null !== $this->endDate) {
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
        $selectedMediaTypes = MediaType::getMediaTypes();
        $filterInMediaTypes = $this->getFilterInMediaTypes();
        $filterOutMediaTypes = $this->getFilterOutMediaTypes();

        if (0 < count($filterInMediaTypes)) {
            $selectedMediaTypes = $filterInMediaTypes;
        } else if (0 < count($filterOutMediaTypes)) {
            $selectedMediaTypes = array_diff($selectedMediaTypes, $filterOutMediaTypes);
        }

        if (0 < count($selectedMediaTypes)) {
            $query .= 'AND (';

            $i = 0;
            foreach($selectedMediaTypes as $mediaType) {
                if ($i > 0) $query .= ' OR ';
                $query .= "p.media_type = '$mediaType'";
                $i++;
            }

            $query .= ")\n";
        }

        return $query;
    }

    /**
     * Returns a string to only include certain languages.
     *
     * @return string
     */
    protected function filterLanguages(): string
    {
        $query = '';
        $filterInLanguages = $this->getFilterInLanguages();
        $filterOutLanguages = $this->getFilterOutLanguages();

        if (0 < count($filterInLanguages)) {
            foreach($filterInLanguages as $language) {
                $query .= "AND p.file_name LIKE '%$language%'\n";
            }
        } else if (0 < count($filterOutLanguages)) {
            foreach($filterOutLanguages as $language) {
                $query .= "AND p.file_name NOT LIKE '%$language%'\n";
            }
        }

        return $query;
    }

    /**
     * Returns a string to only include certain bots.
     *
     * @return string
     */
    protected function filterBots(): string
    {
        $query = '';
        $filterInbots = $this->getFilterInBots();
        $filterOutbots = $this->getFilterOutBots();

        if (0 < count($filterInbots)) {
            $query = 'AND b.id IN (' . implode(',', $filterInbots) . ')' . "\n";
        } else if (0 < count($filterOutbots)) {
            $query = 'AND b.id NOT IN (' . implode(',', $filterOutbots) . ')' . "\n";
        }

        return $query;
    }

    /**
     * Returns a string to only include certain resolutions.
     *
     * @return string
     */
    protected function filterResolutions(): string
    {
        $query = '';
        $filterInResolutions = $this->getFilterInResolutions();
        $filterOutResolutions = $this->getFilterOutResolutions();

        if (0 < count($filterInResolutions)) {
            $query .= 'AND (';
                $i = 0;
                foreach($filterInResolutions as $res) {
                    if ($i > 0) $query .= ' OR ';
                    $query .= "p.file_name LIKE '%$res%'";
                    $i++;
                }
            $query .= ")\n";
        } else if (0 < count($filterOutResolutions)) {
            foreach($filterInResolutions as $res) {
                $query .= "AND p.file_name NOT LIKE '%$res%'\n";
            }
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
        $filterInDynamicRange = $this->getFilterInDynamicRange();
        $filterOutDynamicRange = $this->getFilterOutDynamicRange();

        if (0 < count($filterInDynamicRange)) {
            $query .= 'AND (';
                $i = 0;
                foreach($filterInDynamicRange as $range) {
                    if ($i > 0) $query .= ' OR ';
                    $query .= "p.file_name LIKE '%$range%'";
                    $i++;
                }
            $query .= ")\n";
        } else if (0 < count($filterOutDynamicRange)) {
            foreach($filterInDynamicRange as $range) {
                $query .= "AND p.file_name NOT LIKE '%$range%'\n";
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
        $filterInFileExtensions = $this->getFilterInFileExtensions();
        $filterOutFileExtensions = $this->getFilterOutFileExtensions();

        if (0 < count($filterInFileExtensions)) {
            $regex = $this->makeFileExtensionRegEx($filterInFileExtensions);
            $query = "AND p.file_name RLIKE '$regex'\n";
        } else if (0 < count($filterOutFileExtensions)) {
            $regex = $this->makeFileExtensionRegEx($filterOutFileExtensions);
            $query = "AND p.file_name NOT RLIKE '$regex'\n";
        }

        return $query;
    }

    /**
     * Returns a string that disables results where bot names are empty.
     *
     * @return string
     */
    protected function filterEmptyBotNames(): string
    {
        return 'AND b.nick <>' . "\n";
    }

    /**
     * Returns the component of the query that does the From and Joins of the tables.
     *
     * @return string
     */
    protected function getQueryFrom(): string
    {
        $query = 'FROM mcol.packets p' . "\n";
        $query .= 'JOIN mcol.networks n on p.network_id = n.id' . "\n";
        $query .= 'JOIN mcol.bots b on p.bot_id = b.id' . "\n";
        $query .= 'JOIN mcol.`channels` c on p.channel_id = c.id' . "\n";
        $query .= 'INNER JOIN mcol.file_first_appearances f on p.file_name = f.file_name' . "\n";
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
        $query .= ', p.gets';
        $query .= ', p.size';
        $query .= ', p.media_type';
        $query .= ', p.file_name';
        $query .= ', n.name';
        $query .= ', b.nick';
        $query .= ', p.number';
        $query .= ', f.created_at as first_appearance';
        $query .= "\n";

        return $query;
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
        return "ORDER BY $orderColumn $direction";
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
                $expandedMediaLanguageList[] = $expanded[$language];
            }
        }

        return $expandedMediaLanguageList;
    }

    protected function makeFileExtensionRegEx(array $fileExtensionList): string
    {
        $regex = '';
        $i = 0;
        foreach($fileExtensionList as $ex) {
            if ($i > 0) $regex .= '|';
            $regex .= ".*$ex$";
        }

        return $regex;
    }

    /**
     * Ensures the value of direction is within the list of available direction options.
     *
     * @param $direction
     * @return string
     */
    protected function sanitizeDirection(string $direction): string
    {
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
        return FileExtension::whereIn('id', $fileExtensionList)->pluck('name')->toArray();
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
        $this->filterInDynamicRange = $this->sanitizeMediaDynamicRangeList($filterInDynamicRange);

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
        $this->filterOutDynamicRange =  $this->sanitizeMediaDynamicRangeList($filterOutDynamicRange);
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
     * @return  string
     */ 
    public function getSearchString(): string
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
    public function getDirection()
    {
        if (null === $this->direction) {
            $this->getDefaultDirection();
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
    public function getOrder(): string
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
}