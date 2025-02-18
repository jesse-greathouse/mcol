<?php

namespace App\Http\Resources;

use Illuminate\Http\Request,
    Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class PacketSearchResultResource
 */
class PacketSearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
            'packet_search' => $this->packetSearch,
            'packet'        => $this->packet,
        ];
    }
}
