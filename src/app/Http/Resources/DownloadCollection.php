<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DownloadCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = DownloadResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, DownloadResource>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
