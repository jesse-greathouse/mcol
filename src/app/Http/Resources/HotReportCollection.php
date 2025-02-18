<?php

namespace App\Http\Resources;

use Illuminate\Http\Request,
    Illuminate\Http\Resources\Json\ResourceCollection;

class HotReportCollection extends ResourceCollection
{
    /** @var string */
    public $collects = HotReportResource::class;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
