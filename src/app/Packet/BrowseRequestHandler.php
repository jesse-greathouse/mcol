<?php
namespace App\Packet;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

use \DateTime;

class BrowseRequestHandler
{

    const PAGE_KEY = 'page';
    const RPP_KEY = 'rpp';
    const ORDER_KEY = 'order';
    const DIRECTION_KEY = 'direction';
    const START_DATE_KEY = 'start_date';
    const END_DATE_KEY = 'end_date';
    const IN_BOTS_KEY = 'in_bots';
    const OUT_BOTS_KEY = 'out_bots';
    const IN_NICK_KEY = 'in_nick';
    const OUT_NICK_KEY = 'out_nick';
    const IN_LANGUAGE_KEY = 'in_language';
    const OUT_LANGUAGE_KEY = 'out_language';
    const SEARCH_STRING_KEY = 'search_string';
    const IN_MEDIA_TYPE_KEY = 'in_media_type';
    const OUT_MEDIA_TYPE_KEY = 'out_media_type';
    const IN_RESOLUTIONS_KEY = 'in_resolutions';
    const OUT_RESOLUTIONS_KEY = 'out_resolutions';
    const IN_DYNAMIC_RANGE_KEY = 'in_dynamic_range';
    const OUT_DYNAMIC_RANGE_KEY = 'out_dynamic_range';
    const IN_FILE_EXTENSION_KEY = 'in_file_extension';
    const OUT_FILE_EXTENSION_KEY = 'out_file_extension';

    /**
     * A Web Request
     *
     * @var Request
     */
    protected Request $request;

    /**
     * A Browse object
     *
     * @var Browse
     */
    protected Browse $browse;

    /**
     * Instansiates a Browse object.
     * Browse is a comprehensive tool for creating SQL queries for the packets table.
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
            self::SEARCH_STRING_KEY => $this->browse->getSearchString(),
            self::IN_MEDIA_TYPE_KEY => $this->browse->getFilterInMediaTypes(),
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
        if ($this->request->has(self::PAGE_KEY) && null !== $this->request->input(self::PAGE_KEY)) {
            $this->browse->setPage($this->request->input(self::PAGE_KEY));
        }
    }

    /**
     * Handle Rpp input.
     *
     * @return void
     */
    protected function rpp(): void
    {
        if ($this->request->has(self::RPP_KEY) && null !== $this->request->input(self::RPP_KEY)) {
            $this->browse->setRpp($this->request->input(self::RPP_KEY));
        }
    }

    /**
     * Handle Order input.
     *
     * @return void
     */
    protected function order(): void
    {
        if ($this->request->has(self::ORDER_KEY) && null !== $this->request->input(self::ORDER_KEY)) {
            $order = strtolower($this->request->input(self::ORDER_KEY));
            if (in_array($order, Browse::getOrderOptions())) {
                $this->browse->setOrder($order);
            }
        }
    }

    /**
     * Handle direction input.
     *
     * @return void
     */
    protected function direction(): void
    {
        if ($this->request->has(self::DIRECTION_KEY) && null !== $this->request->input(self::DIRECTION_KEY)) {
            $direction = strtolower($this->request->input(self::DIRECTION_KEY));
            if (in_array($direction, Browse::getDirectionOptions())) {
                $this->browse->setDirection($direction);
            }
        }
    }

    /**
     * Handle search string input.
     *
     * @return void
     */
    protected function search(): void
    {
        if ($this->request->has(self::SEARCH_STRING_KEY) && null !== $this->request->input(self::SEARCH_STRING_KEY)) {
            $this->browse->setSearchString($this->request->input(self::SEARCH_STRING_KEY));
        }
    }

    /**
     * DateTime to start the chronology of the results
     * 
     * @return void
     */
    protected function startDate(): void
    {
        if ($this->request->has(self::START_DATE_KEY) && null !== $this->request->input(self::START_DATE_KEY)) {
            $startDate = new DateTime($this->request->input(self::START_DATE_KEY));
            $this->browse->setStartDate($startDate);
        }
    }

    /**
     * DateTime to end the chronology of the results
     * 
     * @return void
     */
    protected function endDate(): void
    {
        if ($this->request->has(self::END_DATE_KEY) && null !== $this->request->input(self::END_DATE_KEY)) {
            $endDate = new DateTime($this->request->input(self::END_DATE_KEY));
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
        if ($this->request->has(self::IN_BOTS_KEY)) {
            $this->browse->setFilterInBots($this->request->input(self::IN_BOTS_KEY));
        } else if ($this->request->has(self::OUT_BOTS_KEY)) {
            $this->browse->setFilterOutBots($this->request->input(self::OUT_BOTS_KEY));
        }
    }

    /**
     * Handle Bots input.
     *
     * @return void
     */
    protected function nicks(): void
    {
        if ($this->request->has(self::IN_NICK_KEY)) {
            $this->browse->setFilterInNickMask($this->request->input(self::IN_NICK_KEY));
        } else if ($this->request->has(self::OUT_NICK_KEY)) {
            $this->browse->setFilterOutNickMask($this->request->input(self::OUT_NICK_KEY));
        }
    }

    /**
     * Handle MediaTypes input.
     *
     * @return void
     */
    protected function mediaTypes(): void
    {
        if ($this->request->has(self::IN_MEDIA_TYPE_KEY)) {
            $this->browse->setFilterInMediaTypes($this->request->input(self::IN_MEDIA_TYPE_KEY));
        } else if ($this->request->has(self::OUT_MEDIA_TYPE_KEY)) {
            $this->browse->setFilterOutMediaTypes($this->request->input(self::OUT_MEDIA_TYPE_KEY));
        }
    }

    /**
     * Handle Language input.
     *
     * @return void
     */
    protected function languages(): void
    {
        if ($this->request->has(self::IN_LANGUAGE_KEY)) {
            $this->browse->setFilterInLanguages($this->request->input(self::IN_LANGUAGE_KEY));
        } else if ($this->request->has(self::OUT_LANGUAGE_KEY)) {
            $this->browse->setFilterOutLanguages($this->request->input(self::OUT_LANGUAGE_KEY));
        }
    }

    /**
     * Handle File Extension input.
     *
     * @return void
     */
    protected function fileExtensions(): void
    {
        if ($this->request->has(self::IN_FILE_EXTENSION_KEY)) {
            $this->browse->setFilterInFileExtensions($this->request->input(self::IN_FILE_EXTENSION_KEY));
        } else if ($this->request->has(self::OUT_FILE_EXTENSION_KEY)) {
            $this->browse->setFilterOutFileExtensions($this->request->input(self::OUT_FILE_EXTENSION_KEY));
        }
    }

    /**
     * Handle Resolutions input.
     *
     * @return void
     */
    protected function resolutions(): void
    {
        if ($this->request->has(self::IN_RESOLUTIONS_KEY)) {
            $this->browse->setFilterInResolutions($this->request->input(self::IN_RESOLUTIONS_KEY));
        } else if ($this->request->has(self::OUT_RESOLUTIONS_KEY)) {
            $this->browse->setFilterOutResolutions($this->request->input(self::OUT_RESOLUTIONS_KEY));
        }
    }

    /**
     * Handle Dynamic Range input.
     *
     * @return void
     */
    protected function dynamicRanges(): void
    {
        if ($this->request->has(self::IN_DYNAMIC_RANGE_KEY)) {
            $this->browse->setFilterInDynamicRange($this->request->input(self::IN_DYNAMIC_RANGE_KEY));
        } else if ($this->request->has(self::OUT_DYNAMIC_RANGE_KEY)) {
            $this->browse->setFilterOutDynamicRange($this->request->input(self::OUT_DYNAMIC_RANGE_KEY));
        }
    }

}
