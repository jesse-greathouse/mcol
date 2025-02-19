<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory,
    Illuminate\Database\Eloquent\Model,
    Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\Download; // Grouped for future expansion

/**
 * Class DownloadDestination
 *
 * Represents a destination for download status management.
 *
 * @package App\Models
 */
class DownloadDestination extends Model
{
    use HasFactory;

    /** @var array */
    protected $guarded = [];

    /** @const string Status constant for incomplete downloads */
    const STATUS_INCOMPLETE = 'incomplete';

    /** @const string Status constant for completed downloads */
    const STATUS_COMPLETED = 'completed';

    /** @const string Status constant for queued downloads */
    const STATUS_QUEUED = 'queued';

    /** @const string Status constant for waiting downloads */
    const STATUS_WAITING = 'waiting';

    /**
     * Get all available download statuses.
     *
     * @return string[] List of all available statuses
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
     * Check if the download destination status is "queued".
     *
     * @return bool True if the status is "queued", false otherwise
     */
    public function isQueued(): bool
    {
        return $this->status === self::STATUS_QUEUED;
    }

    /**
     * Check if the download destination status is "waiting".
     *
     * @return bool True if the status is "waiting", false otherwise
     */
    public function isWaiting(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    /**
     * Get the associated Download model.
     *
     * @return BelongsTo Relationship between DownloadDestination and Download
     */
    public function download(): BelongsTo
    {
        return $this->belongsTo(Download::class);
    }
}
