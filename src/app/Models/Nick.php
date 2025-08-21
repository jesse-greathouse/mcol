<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Nick
 *
 * This model represents a Nick, which belongs to a Network.
 *
 * @property int $id
 * @property int $network_id
 * @property \App\Models\Network $network
 */
class Nick extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * Get the content for the network.
     *
     * Defines the inverse relationship between Nick and Network.
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }
}
