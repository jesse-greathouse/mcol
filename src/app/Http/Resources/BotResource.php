<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class BotResource
 *
 * This resource transforms a bot's data into an array format for API responses.
 */
class BotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request  The current HTTP request instance.
     * @return array<string, mixed> Transformed bot data.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nick' => $this->nick,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'network' => $this->network,
        ];
    }
}
