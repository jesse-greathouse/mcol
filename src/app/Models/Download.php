<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Download extends Model
{
    use HasFactory;

    protected $guarded = [];

    const STATUS_INCOMPLETE = 'INCOMPLETE';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_QUEUED = 'QUEUED';

    /**
     * Creates a boolean that designates this attr as queued or not queued.
     *
     * @return boolean
     */
    public function isQueued(): bool
    {
        return ($this->status === Download::STATUS_QUEUED) ? true : false;
    }

    /**
     * Get the instance of the Packet.
     */
    public function packet(): BelongsTo
    {
        return $this->belongsTo(Packet::class);
    }
    
}
