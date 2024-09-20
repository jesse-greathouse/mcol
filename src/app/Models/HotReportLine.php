<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotReportLine extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get get the HotReport of this result.
     */
    public function hotReport(): BelongsTo
    {
        return $this->belongsTo(HotReport::class);
    }
}
