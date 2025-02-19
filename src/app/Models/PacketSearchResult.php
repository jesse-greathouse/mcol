<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory,
    Illuminate\Database\Eloquent\Model,
    Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PacketSearchResult
 *
 * Represents a single line of a packet search report, linking to a PacketSearch and a Packet.
 *
 * @package App\Models
 */
class PacketSearchResult extends Model
{
    use HasFactory;

    /**
     * @var array The attributes that are mass assignable.
     */
    protected $guarded = [];

    /**
     * Get the PacketSearch associated with this result.
     *
     * Efficiently retrieves the related PacketSearch model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function packetSearch(): BelongsTo
    {
        return $this->belongsTo(PacketSearch::class);
    }

    /**
     * Get the Packet associated with this result.
     *
     * Efficiently retrieves the related Packet model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function packet(): BelongsTo
    {
        return $this->belongsTo(Packet::class);
    }
}
