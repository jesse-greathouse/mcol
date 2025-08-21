<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     */
    public function hotReportLines(): HasMany
    {
        return $this->hasMany(HotReportLine::class);
    }

    /**
     * Get the channel associated with this report.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}
