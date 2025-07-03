<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class representing a collection of download queue resources.
 */
class DownloadQueueCollection extends ResourceCollection
{
    /**
     * The resource that this collection collects.
     *
     * @var string
     */
    public $collects = DownloadQueueResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Reusing parent class's method for transformation
        return parent::toArray($request);
    }
}
