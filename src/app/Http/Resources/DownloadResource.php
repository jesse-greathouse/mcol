<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DownloadResource extends JsonResource
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
            'file_uri'          => $this->file_uri,
            'file_name'         => $this->file_name,
            'status'            => $this->status,
            'enabled'           => $this->enabled,
            'file_size_bytes'   => $this->file_size_bytes,
            'progress_bytes'    => $this->progress_bytes,
            'queued_total'      => $this->queued_total,
            'queued_status'     => $this->queued_status,
            'packet'            => $this->packet,
            'destination'       => $this->destination,
            'meta'              => $this->meta,
        ];
    }
}
