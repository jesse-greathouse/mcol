<?php

namespace App\Http\Resources;

use Illuminate\Http\Request,
    Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource representation of a Hot Report.
 */
class HotReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request The incoming request instance.
     * @return array<string, mixed> The transformed resource data.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'summary' => $this->resource->summary,
            'channel' => $this->resource->channel,
            'lines' => $this->resource->hotReportLines,
        ];
    }
}
