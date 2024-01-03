<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class FileExtension extends Model
{
    use HasFactory;

    /**
     * Get the servers for the network.
     */
    public function mediaTypes(): BelongsToMany
    {
        return $this->belongsToMany(MediaType::class);
    }
}
