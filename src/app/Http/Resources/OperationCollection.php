<?php

namespace App\Http\Resources;

use Illuminate\Http\Request,
    Illuminate\Http\Resources\Json\ResourceCollection;

class OperationCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = OperationResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array<int|string, OperationResource>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
