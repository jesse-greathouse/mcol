<?php

namespace App\Http\Resources;

use Illuminate\Http\Request,
    Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class PacketCollection
 *
 * @package App\Http\Resources
 */
class PacketCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = PacketResource::class; // Specifies the resource being collected

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<int|string, PacketResource>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request); // Return the array representation from parent
    }
}
