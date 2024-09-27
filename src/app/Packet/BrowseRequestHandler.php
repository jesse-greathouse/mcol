<?php
namespace App\Packet;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BrowseRequestHandler
{

    const PAGE_KEY = 'page';
    const RPP_KEY = 'rpp';
    const ORDER_KEY = 'order';
    const DIRECTION_KEY = 'direction';
    const IN_BOTS_KEY = 'in_bots';
    const OUT_BOTS_KEY = 'out_bots';
    const IN_NICK_KEY = 'in_nick';
    const OUT_NICK_KEY = 'out_nick';
    const IN_MEDIA_TYPE_KEY = 'in_media_type';
    const OUT_MEDIA_TYPE_KEY = 'out_media_type';
    const IN_LANGUAGE_KEY = 'in_language';
    const OUT_LANGUAGE_KEY = 'out_language';

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
        $this->bots();
        $this->nicks();
        $this->mediaTypes();
        $this->language();
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
        if ($this->request->has(self::PAGE_KEY)) {
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
        if ($this->request->has(self::RPP_KEY)) {
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
        if ($this->request->has(self::ORDER_KEY)) {
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
        if ($this->request->has(self::DIRECTION_KEY)) {
            $direction = strtolower($this->request->input(self::DIRECTION_KEY));
            if (in_array($direction, Browse::getDirectionOptions())) {
                $this->browse->setDirection($direction);
            }
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
    protected function language(): void
    {
        if ($this->request->has(self::IN_LANGUAGE_KEY)) {
            $this->browse->setFilterInLanguages($this->request->input(self::IN_LANGUAGE_KEY));
        } else if ($this->request->has(self::OUT_LANGUAGE_KEY)) {
            $this->browse->setFilterOutLanguages($this->request->input(self::OUT_LANGUAGE_KEY));
        }
    }

}
