<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Network extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the servers for the network.
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * Get first server on the list.
     */
    public function firstServer(): HasOne
    {
        return $this->hasOne(Server::class)->oldestOfMany();
    }

}
