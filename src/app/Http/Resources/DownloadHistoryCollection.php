<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DownloadHistoryCollection extends ResourceCollection
{
    /**
     * The resource that this collection collects.
     *
     * @var class-string
     */
    public $collects = DownloadHistoryResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, \App\Http\Resources\DownloadHistoryResource>
     */
    public function toArray(Request $request): array
    {
        // Reusing parent transformation for collection conversion
        return parent::toArray($request);
    }
}
