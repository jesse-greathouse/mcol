<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PacketSearchResult extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get get the PacketSearch of this result.
     */
    public function packetSearch(): BelongsTo
    {
        return $this->belongsTo(PacketSearch::class);
    }

    /**
     * Get get the Packet of this result.
     */
    public function packet(): BelongsTo
    {
        return $this->belongsTo(Packet::class);
    }
}
