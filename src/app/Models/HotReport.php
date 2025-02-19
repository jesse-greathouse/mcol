<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory,
    Illuminate\Database\Eloquent\Model,
    Illuminate\Database\Eloquent\Relations\HasMany,
    Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class HotReport
 *
 * @property int $id
 * @property Channel $channel
 * @property HotReportLine[] $hotReportLines
 */
class HotReport extends Model
{
    use HasFactory;

    /** @var array The attributes that are mass assignable. */
    protected $guarded = [];

    /**
     * Get the hotReportLines associated with this report.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hotReportLines(): HasMany
    {
        return $this->hasMany(HotReportLine::class);
    }

    /**
     * Get the channel associated with this report.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}
