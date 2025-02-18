<?php

namespace App\Http\Resources;

use Illuminate\Http\Request,
    Illuminate\Http\Resources\Json\JsonResource;

class PacketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'number'     => $this->number,
            'gets'       => $this->gets,
            'size'       => $this->size,
            'file_name'  => $this->file_name,
            'bot'        => $this->bot,
            'channel'    => $this->channel,
            'network'    => $this->network,
            'meta'       => $this->meta,
        ];
    }
}
