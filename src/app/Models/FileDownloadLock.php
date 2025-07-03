<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FileDownloadLock
 *
 * Represents a model for the file download lock.
 * This class manages the locking mechanism for file downloads to avoid concurrent access issues.
 */
class FileDownloadLock extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];
}
