<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory,
    Illuminate\Database\Eloquent\Model,
    Illuminate\Database\Eloquent\Relations\BelongsTo,
    Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Download
 *
 * @package App\Models
 *
 * @property string $status
 * @property array $meta
 * @property \App\Models\Packet $packet
 * @property \App\Models\DownloadDestination $destination
 */
class Download extends Model
{
    use HasFactory;

    // Guard all attributes except the ones that are explicitly set in the fillable property
    protected $guarded = [];

    // Casts attributes to specific data types
    protected $casts = [
        'meta' => 'array',
    ];

    // Define constants for status types
    public const STATUS_INCOMPLETE = 'incomplete';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_QUEUED = 'queued';

    /**
     * Determine if the download status is "queued".
     *
     * @return bool
     */
    public function isQueued(): bool
    {
        return $this->status === self::STATUS_QUEUED;
    }

    /**
     * Get the associated Packet for this Download.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function packet(): BelongsTo
    {
        return $this->belongsTo(Packet::class);
    }

    /**
     * Get the associated DownloadDestination for this Download.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function destination(): HasOne
    {
        return $this->hasOne(DownloadDestination::class);
    }
}
