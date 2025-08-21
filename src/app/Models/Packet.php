<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Searchable;

/**
 * Represents a file packet offered by a file-sharing bot in an IRC network.
 *
 * In the context of an IRC client, a `Packet` is a unit of data associated with a file being shared
 * by a bot over an IRC network. The `Packet` model stores information such as the file's name and metadata
 * about the file transfer, which can include properties like the file's size, hash, or other relevant
 * information for the download process.
 *
 * This model is associated with various other models that define the relationships between the packet,
 * the bot offering the file, the network the bot is part of, and the specific IRC channel where the
 * packet is available for download.
 *
 * The `Packet` model is searchable via Laravel Scout, which integrates with a full-text search system.
 * This searchability is defined by the `toSearchableArray()` method and indexed using the `file_name` attribute.
 * The `file_name` is the primary searchable field and is indexed for efficient querying, allowing the client
 * to quickly find packets by their filenames in a large dataset of files offered by bots in various channels.
 *
 * The searchable index uses full-text indexing provided by Laravel Scout, which improves search performance
 * by allowing partial matches, fuzzy searching, and more advanced search queries. This is beneficial in scenarios
 * where the client needs to retrieve packets based on similar or incomplete filenames, or where variations in
 * spelling or formatting could occur.
 *
 * @property string $file_name The name of the file associated with the packet.
 * @property array $meta Additional metadata about the packet, such as file size or hash.
 * @property int $bot_id The ID of the bot offering the file in the packet.
 * @property int $network_id The ID of the IRC network the bot belongs to.
 * @property int $channel_id The ID of the IRC channel where the packet is available.
 */
class Packet extends Model
{
    use HasFactory, Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Get the bot associated with the packet.
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /**
     * Get the network associated with the packet.
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    /**
     * Get the channel associated with the packet.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'packet_search_index';
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
