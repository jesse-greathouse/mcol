<?php

namespace App\Http\Resources;

use Illuminate\Http\Request,
    Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class ClientCollection
 *
 * A collection of Client resources.
 *
 * @see https://regex101.com/r/9cPa1z/1  For regex information
 */
class ClientCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = ClientResource::class; // Resource type for the collection.

    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Calls the parent toArray method to transform the collection.
        return parent::toArray($request);
    }
}
