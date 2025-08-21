<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class BrowseResource
 *
 * This class transforms the `Browse` model into an array for API responses.
 */
class BrowseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'gets' => $this->gets,
            'size' => $this->size,
            'media_type' => $this->media_type,
            'file_name' => $this->file_name,
            'network' => $this->network,
            'bot_id' => $this->bot_id,
            'nick' => $this->nick,
            'number' => $this->number,
            'first_appearance' => $this->updated_at,
            'meta' => json_decode($this->meta),
        ];
    }
}
