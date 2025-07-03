<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Bot
 *
 * The Bot model represents a bot in the system, associating it with a specific network.
 *
 * @property int $id The unique identifier for the bot.
 * @property int $network_id The ID of the network the bot belongs to.
 */
class Bot extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the content for the network.
     *
     * Defines the relationship where each bot belongs to a network.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }
}
