<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array'
    ];

    public function children(): HasMany
    {
        return $this->hasMany(Channel::class, 'channel_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class, 'network_id');
    }
}
