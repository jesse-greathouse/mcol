<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory,
    Illuminate\Database\Eloquent\Model,
    Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Server
 *
 * Represents a server that belongs to a specific network.
 *
 * @package App\Models
 */
class Server extends Model
{
    use HasFactory;

    /**
     * @var array The attributes that are mass assignable.
     */
    protected $guarded = [];

    /**
     * Get the Network associated with this server.
     *
     * Efficiently retrieves the related Network model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }
}
