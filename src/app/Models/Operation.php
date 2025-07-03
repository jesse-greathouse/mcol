<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Operation
 *
 * This model represents the operations that can be performed within the system.
 * It includes the statuses and a relationship to the Instance model.
 *
 * @property string $status The current status of the operation.
 */
class Operation extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_PENDING = 'PENDING';

    const STATUS_COMPLETED = 'COMPLETED';

    const STATUS_FAILED = 'FAILED';

    /** @var array The attributes that are not mass assignable */
    protected $guarded = [];

    /**
     * Define the relationship between Operation and Instance.
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }
}
