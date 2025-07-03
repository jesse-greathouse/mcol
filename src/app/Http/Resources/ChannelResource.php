<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ChannelResource
 */
class ChannelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request  The incoming request instance
     * @return array<string, mixed> The transformed resource data
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'topic' => $this->topic,
            'users' => $this->users,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'parent' => $this->parent,
            'network' => $this->network,
        ];
    }
}
