<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Operation extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'PENDING';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_FAILED = 'FAILED';

    protected $guarded = [];

    /**
     * Get the content for the nick.
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }
}
