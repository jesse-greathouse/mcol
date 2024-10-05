<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DownloadQueueResource extends JsonResource
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
            'packet'            => $this->packet,
            'status'            => $this->status,
            'queued_status'     => $this->queued_status,
            'queued_total'      => $this->queued_total,
            'file_size_bytes'   => $this->file_size_bytes,
            'progress_bytes'    => $this->progress_bytes,
            'file_uri'          => $this->file_uri,
        ];
    }
}
