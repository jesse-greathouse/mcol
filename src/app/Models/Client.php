<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory,
    Illuminate\Database\Eloquent\Model,
    Illuminate\Database\Eloquent\Relations\BelongsTo,
    Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Client model class.
 *
 * Represents a client in the system with relationships to other entities like Nick, Network, and Instance.
 */
class Client extends Model
{
    use HasFactory;

    /** @var array The attributes that are mass assignable. */
    protected $guarded = [];

    /** @var array The attributes that should be cast to native types. */
    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Get the content for the nick.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function nick(): BelongsTo
    {
        return $this->belongsTo(Nick::class);
    }

    /**
     * Get the content for the network.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    /**
     * Get the instance associated with this client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function instance(): HasOne
    {
        return $this->hasOne(Instance::class);
    }
}
