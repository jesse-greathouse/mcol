<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource,
    Illuminate\Http\Request;

class OperationResource extends JsonResource
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
            'id'         => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'command'    => $this->command,
            'status'     => $this->status,
            'enabled'    => $this->enabled,
            'instance'   => $this->instance,
        ];
    }
}
