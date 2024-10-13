<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadDestination extends Model
{
    use HasFactory;

    protected $guarded = [];

    const STATUS_INCOMPLETE = 'incomplete';
    const STATUS_COMPLETED = 'completed';
    const STATUS_QUEUED = 'queued';
    const STATUS_WAITING = 'waiting';

    /**
     * Returns a list of all available statuses.
     *
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_COMPLETED,
            self::STATUS_INCOMPLETE,
            self::STATUS_QUEUED,
            self::STATUS_WAITING,
        ];
    }

    /**
     * Creates a boolean that designates this attr as queued or not queued.
     *
     * @return boolean
     */
    public function isQueued(): bool
    {
        return ($this->status === self::STATUS_QUEUED) ? true : false;
    }

    /**
     * Creates a boolean that designates this attr as queued or not queued.
     *
     * @return boolean
     */
    public function isWaiting(): bool
    {
        return ($this->status === self::STATUS_WAITING) ? true : false;
    }

    /**
     * Get the instance of the Download.
     */
    public function download(): BelongsTo
    {
        return $this->belongsTo(Download::class);
    }
}
