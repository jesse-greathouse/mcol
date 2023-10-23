<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Instance extends Model
{
    use HasFactory;

    /**
     * Get the content for the nick.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
