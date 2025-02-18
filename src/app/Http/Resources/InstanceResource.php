<?php

namespace App\Http\Resources;

use Illuminate\Http\Request,
    Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class InstanceResource
 */
class InstanceResource extends JsonResource
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
            'id'             => $this->id,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
            'pid'            => $this->pid,
            'log_uri'        => $this->log_uri,
            'status'         => $this->status,
            'desired_status' => $this->desired_status,
            'enabled'        => $this->enabled,
            'client'         => $this->client,
        ];
    }
}
