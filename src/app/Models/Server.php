<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Server extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the content for the network.
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }
    
}
