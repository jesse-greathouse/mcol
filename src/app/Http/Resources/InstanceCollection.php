<?php

namespace App\Http\Resources;

use Illuminate\Http\Request,
    Illuminate\Http\Resources\Json\ResourceCollection;

class InstanceCollection extends ResourceCollection
{
    /** @var class-string<InstanceResource> The resource collected by this collection. */
    public string $collects = InstanceResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, InstanceResource>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
