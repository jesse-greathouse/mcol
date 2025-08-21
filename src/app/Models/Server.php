<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Server
 *
 * Represents a server that belongs to a specific network.
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
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }
}
