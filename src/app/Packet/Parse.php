<?php

namespace App\Packet;

use Illuminate\Contracts\Cache\Repository;

use App\Jobs\GeneratePacketMeta,
    App\Models\Bot,
    App\Models\Packet,
    App\Models\Channel,
    App\Models\FileFirstAppearance,
    App\Packet\MediaType\MediaTypeGuesser;

class Parse {
    /**
     * Regular expression pattern for parsing packet messages.
     */
    private const PACKET_MASK = '/#(\d+)\s+(\d+)x\s+\[([0-9B-k\.\s]+)\]\s+(.+)/';

    /**
     * Processes a packet text line and returns a persisted Packet object.
     *
     * @param string $text The raw packet message.
     * @param Bot $bot The bot that reported the packet.
     * @param ?Channel $channel The channel associated with the bot, if available.
     * @param ?Repository $cache An optional cache repository for performance optimization.
     * @return Packet|null The parsed and persisted packet, or null if parsing fails.
     */
    public static function packet(string $text, Bot $bot, ?Channel $channel = null, ?Repository $cache = null): ?Packet {
        $message = self::cleanMessage($text);
        $key = self::makeMessageCacheKey($message);

        // Attempt to retrieve parsed packet data from cache
        $matches = $cache?->get($key);
        if ($matches) {
            $matches = self::unserialize($matches);
            if (!is_array($matches) || count($matches) < 5) {
                $matches = null; // Invalidate bad cache entries
            }
        }

        // If not cached, perform regex parsing
        if (!$matches) {
            preg_match(self::PACKET_MASK, $message, $matches);
            if (count($matches) < 5) return null;
            $cache?->put($key, self::serialize($matches));
        }

        [, $number, $gets, $size, $fileName] = $matches;
        $channel ??= self::getBotChannelByBestGuess($bot);

        // Persist or update the packet record
        $packet = Packet::updateOrCreate(
            ['number' => $number, 'network_id' => $bot->network->id, 'channel_id' => $channel->id, 'bot_id' => $bot->id],
            self::getDataToUpdate($fileName, $size, (int) $gets, (int) $number, $bot, $channel)
        );

        // Queue metadata generation if missing
        if (count($packet->meta) === 0) {
            GeneratePacketMeta::dispatch($packet)->onQueue('meta');
        }

        // Register first appearance of the file if not recorded
        FileFirstAppearance::firstOrCreate(
            ['file_name' => $packet->file_name],
            ['created_at' => $packet->created_at]
        );

        return $packet;
    }

    /**
     * Cleans up the given text by removing redundant spaces and control characters.
     *
     * @param string $text
     * @return string The cleaned-up message.
     */
    public static function cleanMessage(string $text): string {
        return preg_replace(['/\s+/', '/[\x00-\x1F\x7F]/'], [' ', ''], $text);
    }

    /**
     * Determines the data fields that need to be updated for a packet.
     *
     * @param string $fileName
     * @param string $size
     * @param int $gets
     * @param int $number
     * @param Bot $bot
     * @param ?Channel $channel
     * @return array The data fields to be updated in the packet.
     */
    private static function getDataToUpdate(string $fileName, string $size, int $gets, int $number, Bot $bot, ?Channel $channel): array {
        $existingPacket = Packet::where([
            ['number', '=', $number],
            ['network_id', '=', $bot->network->id],
            ['channel_id', '=', $channel->id],
            ['bot_id', '=', $bot->id]
        ])->first();

        $data = [
            'file_name' => $fileName,
            'gets' => $gets,
            'size' => $size,
            'media_type' => (new MediaTypeGuesser($fileName))->guess(),
            'meta' => $existingPacket?->meta ?? [],
        ];

        // If the file name changes, update the creation timestamp
        if ($existingPacket && trim($existingPacket->file_name) !== trim($fileName)) {
            $data['created_at'] = now();
        }

        return $data;
    }

    /**
     * Attempts to determine the most appropriate channel for a bot.
     *
     * @param Bot $bot
     * @return ?Channel The best-guess channel.
     */
    private static function getBotChannelByBestGuess(Bot $bot): ?Channel {
        return Packet::where('bot_id', $bot->id)->latest()->value('channel')
            ?? Packet::where('network_id', $bot->network->id)->latest()->value('channel')
            ?? Channel::where('network_id', $bot->network->id)->first();
    }

    /**
     * Generates a cache-friendly key from a given message.
     *
     * @param string $message
     * @return string The generated cache key.
     */
    private static function makeMessageCacheKey(string $message): string {
        return crc32($message);
    }

    /**
     * Serializes an array into a storable string format.
     *
     * @param array $content
     * @return string The serialized data.
     */
    private static function serialize(array $content): string {
        return msgpack_pack($content);
    }

    /**
     * Unserializes a stored string back into an array.
     *
     * @param string $content
     * @return array The unserialized data.
     */
    private static function unserialize(string $content): array {
        return msgpack_unpack($content) ?: [];
    }
}
