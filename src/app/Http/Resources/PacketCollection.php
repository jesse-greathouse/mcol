<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PacketCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = PacketResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, PacketResource>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
