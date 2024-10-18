<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Laravel\Scout\Attributes\SearchUsingFullText;

class DownloadHistory extends Model
{
    use HasFactory;
    use Searchable;

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array'
    ];

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'download_search_index';
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    #[SearchUsingFullText(['file_name'])]
    public function toSearchableArray(): array
    {
        return [
            'file_name' => $this->file_name,
        ];
    }
}
