<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DownloadHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'file_name'         => $this->file_name,
            'media_type'        => $this->media_type,
            'file_uri'          => $this->file_uri,
            'bot_nick'          => $this->bot_nick,
            'network_name'      => $this->network_name,
            'channel_name'      => $this->channel_name,
            'file_size_bytes'   => $this->file_size_bytes,
        ];
    }
}
