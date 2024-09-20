<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotReport extends Model
{
    protected $guarded = [];

    /**
     * Get the hotReportLines of this search.
     */
    public function hotReportLines(): HasMany
    {
        return $this->hasMany(HotReportLine::class);
    }

    /**
     * Get get the channel of this report.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    use HasFactory;
}
