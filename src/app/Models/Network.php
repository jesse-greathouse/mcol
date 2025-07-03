<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Network model representing a network entity.
 *
 * This model defines relationships to servers associated with the network.
 */
class Network extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the servers for the network.
     *
     * This defines a one-to-many relationship between the network and servers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * Get the first server on the list.
     *
     * This defines a one-to-one relationship but ensures the oldest server is selected.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function firstServer(): HasOne
    {
        return $this->hasOne(Server::class)->oldestOfMany();
    }
}
