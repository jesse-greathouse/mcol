<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PacketSearchCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var class-string<PacketSearchResource>
     */
    public $collects = PacketSearchResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Directly leveraging the parent method to handle the collection transformation
        return parent::toArray($request);
    }
}
