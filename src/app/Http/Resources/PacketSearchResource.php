<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource,
    Illuminate\Http\Request;

class PacketSearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
            'channel'       => $this->channel,
            'results'       => $this->packetSearchResults,
        ];
    }
}
