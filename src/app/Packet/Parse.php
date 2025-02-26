<?php

namespace App\Packet;

use Illuminate\Contracts\Cache\Repository;

use App\Exceptions\NetworkWithNoChannelException,
    App\Jobs\GeneratePacketMeta,
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
     * @param Channel $channel The channel associated with the bot, if available. If not, will be determined by best guess.
     * @param ?Repository $cache An optional cache repository for performance optimization.
     * @return void
     */
    public static function packet(string $text, Bot $bot, ?Channel $channel = null, ?Repository $cache = null): void
    {
        $message = self::cleanMessage($text);
        $matches = self::getPacketMatches($message, $cache);
        if (!$matches) return;

        [, $number, $gets, $size, $fileName] = $matches;

        // If no channel is provided, attempt to get the best-guess channel.
        if ($channel === null) {
            $channel = self::getBotChannelByBestGuess($bot);  // Will throw an exception if no channel is found
        }

        self::retrieveOrCreatePacket($number, $gets, $size, $fileName, $bot, $channel, $cache);
    }

    /**
     * Cleans up the given text by removing redundant spaces and control characters.
     *
     * @param string $text
     * @return string The cleaned-up message.
     */
    public static function cleanMessage(string $text): string
    {
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
    private static function getPacketMatches(string $message, ?Repository $cache): ?array
    {
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
    private static function parseMessage(string $message): ?array
    {
        preg_match(self::PACKET_MASK, $message, $matches);
        return count($matches) >= 5 ? $matches : null;
    }

    /**
     * Retrieves a Packet object from cache or database or creates it if it doesn't exist.
     *
     * @param int $number The packet number.
     * @param int $gets The number of times the packet has been downloaded.
     * @param string $size The file size of the packet.
     * @param string $fileName The name of the file.
     * @param Bot $bot The bot that reported the packet.
     * @param Channel $channel The associated channel.
     * @param ?Repository $cache Optional cache repository for performance optimization.
     */
    private static function retrieveOrCreatePacket(
        int $number,
        int $gets,
        string $size,
        string $fileName,
        Bot $bot,
        Channel $channel,
        ?Repository $cache
    ): void {
        $packetCacheKey = self::makePacketCacheKey($number, $fileName, $bot, $channel);

        if ($cache?->get($packetCacheKey)) {
            return; // Packet already cached.
        }

        $packet = Packet::where('number', $number)
                    ->where('network_id', $bot->network->id)
                    ->where('channel_id', $channel->id)
                    ->where('channel_id', $channel->id)
                    ->where('bot_id', $bot->id)
                    ->first();

        if (!$packet) {
            $packet = new Packet();
            $packet->fill([
                'number'        => $number,
                'network_id'    => $bot->network->id,
                'channel_id'    => $channel->id,
                'bot_id'        => $bot->id,
            ]);
        }

        // If file name is new or missing, reset metadata.
        if (null === $packet->file_name || trim($packet->file_name) !== trim($fileName)) {
            $packet->fill([
                'created_at' => now(),
                'file_name' => trim($fileName),
                'media_type' => (new MediaTypeGuesser($fileName))->guess(),
                'meta' => []
            ]);

            FileFirstAppearance::firstOrCreate(
                ['file_name' => $packet->file_name],
                ['created_at' => $packet->created_at]
            );
        }

        $packet->fill([
            'gets' => $gets,
            'size' => $size
        ])->save();

        if (empty($packet->meta)) {
            GeneratePacketMeta::dispatch($packet)->onQueue('meta');
        } else {
            $cache?->put($packetCacheKey, self::serialize($packet->toArray()), self::PACKET_CACHE_TTL);
        }
    }

    /**
     * Attempts to determine the most appropriate channel for a bot.
     *
     * @param Bot $bot
     * @return Channel The best-guess channel.
     * @throws NetworkWithNoChannelException If no channel can be found.
     */
    private static function getBotChannelByBestGuess(Bot $bot): Channel
    {
        $botId = $bot->id;

        if (isset(self::$channelCache[$botId])) {
            return self::$channelCache[$botId];
        }

        $channel = Packet::where('bot_id', $botId)->latest()->value('channel')
            ?? Packet::where('network_id', $bot->network->id)->latest()->value('channel')
            ?? Channel::where('network_id', $bot->network->id)->first();

        if ($channel === null) {
            throw new NetworkWithNoChannelException('No channel found for network: ' . $bot->network->name);
        }

        return self::$channelCache[$botId] = $channel;
    }

    /**
     * Generates a cache-friendly key from a given message.
     *
     * @param string $message
     * @return string The generated cache key.
     */
    private static function makeMessageCacheKey(string $message): string
    {
        return "parse_message:" . crc32($message);
    }

    /**
     * Generates a cache-friendly key from a packet number, bot, and channel.
     *
     * @param int $number The packet number.
     * @param string $fileName The name of the file.
     * @param Bot $bot The bot that reported the packet.
     * @param Channel $channel The channel associated with the bot, if available.
     *
     * @return string The generated cache key.
     */
    private static function makePacketCacheKey(int $number, string $fileName, Bot $bot, Channel $channel): string
    {
        $serializedFileName = crc32($fileName);
        return "packet:{$bot->network->id}:{$channel->id}:{$bot->id}:$number:$serializedFileName";
    }

    /**
     * Serializes an array into a storable string format.
     *
     * @param array $content
     * @return string The serialized data.
     */
    private static function serialize(array $content): string
    {
        return msgpack_pack($content);
    }

    /**
     * Unserializes a stored string back into an array.
     *
     * @param string $content
     * @return array The unserialized data.
     */
    private static function unserialize(string $content): array
    {
        return msgpack_unpack($content) ?: [];
    }
}
