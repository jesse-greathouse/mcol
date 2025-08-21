<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class HotReportLine
 *
 * Represents a line in the HotReport.
 *
 * @property int $id
 * @property int $hot_report_id
 */
class HotReportLine extends Model
{
    use HasFactory;

    // Guarded attributes, no mass assignment allowed on these.
    protected $guarded = [];

    /**
     * Get the HotReport associated with this HotReportLine.
     *
     * This defines a one-to-many relationship where each HotReportLine
     * belongs to a single HotReport.
     */
    public function hotReport(): BelongsTo
    {
        return $this->belongsTo(HotReport::class);
    }
}
