<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array'
    ];

    /**
     * Get the content for the nick.
     */
    public function nick(): BelongsTo
    {
        return $this->belongsTo(Nick::class);
    }

    /**
     * Get the content for the network.
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    /**
     * Get the instance associated with this client.
     */
    public function instance(): HasOne
    {
        return $this->hasOne(Instance::class);
    }

}
