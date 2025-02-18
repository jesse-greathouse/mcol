<?php

namespace App\Http\Resources;

use Illuminate\Http\Request,
    Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class ChannelCollection
 *
 * @package App\Http\Resources
 */
class ChannelCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = ChannelResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request The incoming request.
     *
     * @return array<int|string, mixed> The transformed resource collection.
     */
    public function toArray(Request $request): array
    {
        // Directly return the parent transformation without additional processing
        return parent::toArray($request);
    }
}
