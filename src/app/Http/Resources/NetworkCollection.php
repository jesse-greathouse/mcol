<?php

namespace App\Http\Resources;

use Illuminate\Http\Request,
    Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class representing a collection of network resources.
 *
 * @method array<int|string, mixed> toArray(Request $request)
 */
class NetworkCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = NetworkResource::class; // Resource class the collection holds.

    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
