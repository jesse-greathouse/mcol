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

    private const PACKET_CACHE_TTL = 86400; // 24 hours in seconds

    /**
     * Stores the botID => Channel map so that lookups dont have to happen more than once.
     */
    private static array $channelCache = [];

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
        $matches = self::getPacketMatches($message, $cache);
        if (!$matches) return null;

        [, $number, $gets, $size, $fileName] = $matches;
        $channel ??= self::getBotChannelByBestGuess($bot);

        return self::retrieveOrCreatePacket($number, $gets, $size, $fileName, $bot, $channel, $cache);
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
     * Takes a chat $message and returns the components of a Packet.
     * Will use cache to avoid running the regex if it has already been done with this message.
     * returns null if the message can't parse according to the pattern.
     *
     * @param string $message The chat message.
     * @param ?Repository $cache An optional cache repository for performance optimization.
     * @return array|null
     */
    private static function getPacketMatches(string $message, ?Repository $cache): ?array {
        if (!$cache) {
            return self::parseMessage($message);
        }

        $key = self::makeMessageCacheKey($message);
        if ($matches = self::unserialize($cache->get($key) ?? '')) {
            return $matches;
        }

        $matches = self::parseMessage($message);
        if ($matches) {
            $cache->put($key, self::serialize($matches));
        }

        return $matches;
    }

    /**
     * Runs a regex pattern against the $message string.
     * Returns null if the format for a packet isn't satisfied.
     *
     * @param string $message The chat message.
     *
     * @return array $matches The chat message.
     */
    private static function parseMessage(string $message): ?array {
        preg_match(self::PACKET_MASK, $message, $matches);
        return count($matches) >= 5 ? $matches : null;
    }

    /**
     * Retreives a Packet object from cache or database
     * or creates it if it doesn't exist.
     *
     * @param int $number The packet number.
     * @param int $gets The amount of time the packet has been downloaded.
     * @param string $size The file size of the packets.
     * @param string fileName The name of the file.
     * @param Bot $bot The bot that reported the packet.
     * @param Channel $channel The channel associated with the bot, if available.
     * @param ?Repository $cache An optional cache repository for performance optimization.
     * @return Packet|null The parsed and persisted packet, or null if parsing fails.
     */
    private static function retrieveOrCreatePacket(int $number, int $gets, string $size, string $fileName, Bot $bot, Channel $channel, ?Repository $cache): Packet {
        $packetCacheKey = self::makePacketCacheKey($number, $bot, $channel);
        if ($cache && ($cachedPacket = $cache->get($packetCacheKey))) {
            return Packet::hydrate([self::unserialize($cachedPacket)])[0];
        }

        $packet = Packet::updateOrCreate(
            ['number' => $number, 'network_id' => $bot->network->id, 'channel_id' => $channel->id, 'bot_id' => $bot->id],
            self::getDataToUpdate($fileName, $size, $gets, $number, $bot, $channel)
        );

        if (count($packet->meta) === 0) {
            GeneratePacketMeta::dispatch($packet)->onQueue('meta');
        } else {
            $cache?->put($packetCacheKey, self::serialize($packet->toArray()), self::PACKET_CACHE_TTL);
        }

        FileFirstAppearance::firstOrCreate(
            ['file_name' => $packet->file_name],
            ['created_at' => $packet->created_at]
        );

        return $packet;
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
     * @return Channel The best-guess channel.
     */
    private static function getBotChannelByBestGuess(Bot $bot): Channel {
        $botId = $bot->id;
        if (isset(self::$channelCache[$botId])) {
            return self::$channelCache[$botId];
        }

        $channel = Packet::where('bot_id', $botId)->latest()->value('channel')
            ?? Packet::where('network_id', $bot->network->id)->latest()->value('channel')
            ?? Channel::where('network_id', $bot->network->id)->first();

        return self::$channelCache[$botId] = $channel;
    }

    /**
     * Generates a cache-friendly key from a given message.
     *
     * @param string $message
     * @return string The generated cache key.
     */
    private static function makeMessageCacheKey(string $message): string {
        return "parse_message:" . crc32($message);
    }

    /**
     * Generates a cache-friendly key from a packet number, bot, and channel.
     *
     * @param int $number The packet number.
     * @param Bot $bot The bot that reported the packet.
     * @param Channel $channel The channel associated with the bot, if available.
     *
     * @return string The generated cache key.
     */
    private static function makePacketCacheKey(int $number, Bot $bot, Channel $channel): string {
        return "packet:{$bot->network->id}:{$channel->id}:{$bot->id}:{$number}";
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
