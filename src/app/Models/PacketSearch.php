<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PacketSearch extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the packetSearchResults of this search.
     */
    public function packetSearchResults(): HasMany
    {
        return $this->hasMany(PacketSearchResult::class);
    }

    /**
     * Get get the channel of this search.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}
