<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory,
    Illuminate\Database\Eloquent\Model,
    Illuminate\Database\Eloquent\Relations\HasMany,
    Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PacketSearch
 *
 * Represents a report initiated by a `!search <string>` message in a chat room.
 * This report tracks the resulting feedback from IRC notices where all the bots
 * offering packets that match the search string reply with the matching results.
 * The feedback is collected and displayed to the user.
 */
class PacketSearch extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the packet search results of this search.
     *
     * @return HasMany
     */
    public function packetSearchResults(): HasMany
    {
        // Return the associated PacketSearchResult models
        return $this->hasMany(PacketSearchResult::class);
    }

    /**
     * Get the channel associated with this search.
     *
     * @return BelongsTo
     */
    public function channel(): BelongsTo
    {
        // Return the associated Channel model
        return $this->belongsTo(Channel::class);
    }
}
