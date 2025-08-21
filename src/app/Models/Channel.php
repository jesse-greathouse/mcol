<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Channel model represents an IRC communication channel with possible child channels and network associations.
 *
 * @property int $id
 * @property int $channel_id
 * @property int $network_id
 * @property array $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Channel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Get all the child channels related to this channel.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'channel_id');
    }

    /**
     * Get the parent channel of this channel.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'channel_id');
    }

    /**
     * Get the network associated with this channel.
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class, 'network_id');
    }
}
