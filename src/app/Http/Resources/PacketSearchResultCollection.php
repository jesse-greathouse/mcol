<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PacketSearchResultCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = PacketSearchResultResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, PacketSearchResultResource>
     */
    public function toArray(Request $request): array
    {
        // Utilize parent method to return transformed collection
        return parent::toArray($request);
    }
}
