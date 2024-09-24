<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PacketSearchResultResource extends JsonResource
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
            'packet_search'     => $this->packetSearch,
            'packet'            => $this->packet,
        ];
    }
}
