<?php

namespace App\Packet;

use Illuminate\Support\Facades\Log;

use App\Media\Application,
    App\Media\Book,
    App\Media\Game,
    App\Media\MediaType,
    App\Media\Movie,
    App\Media\Music,
    App\Media\TvEpisode,
    App\Media\TvSeason,
    App\Models\Bot,
    App\Models\Packet,
    App\Models\Channel,
    App\Models\FileFirstAppearance,
    App\Packet\MediaType\MediaTypeGuesser;

use \Exception;

class Parse {

    const PACKET_MASK = '/#([0-9]+)\s+([0-9]+)x\s+\[([0-9B-k\.\s]+)\]\s+(.+)/';

    const MEDIA_MAP = [
        MediaType::APPLICATION  => Application::class,
        MediaType::BOOK         => Book::class,
        MediaType::GAME         => Game::class,
        MediaType::MOVIE        => Movie::class,
        MediaType::MUSIC        => Music::class,
        MediaType::TV_EPISODE   => TvEpisode::class,
        MediaType::TV_SEASON    => TvSeason::class,
    ];

    /**
     * Takes in a packet Text line and returns a persisted packet object.
     *
     * @param string $text
     * @param Bot $bot
     * @param ?Channel $channel
     * @return Packet|null
     */
    public static function packet(string $text, Bot $bot, Channel $channel = null): Packet|null
    {
        $message = self::cleanMessage($text);

        if (!self::isPacket($message)) {
            return null;
        }

        $channel = (null === $channel) ? self::getBotChannelByBestGuess($bot) : $channel;

        [$number, $gets, $size, $fileName] = self::extract($message);

        if (null !== $fileName) {
            $dataToUpdate = self::getDataToUpdate($fileName, $size, $gets, $number, $bot, $channel);

            $packet = Packet::updateOrCreate(
                ['number' => $number, 'network_id' => $bot->network->id, 'channel_id' => $channel->id, 'bot_id' => $bot->id],
                $dataToUpdate
            );

            // Update First Appearance Table if no entry is present.
            FileFirstAppearance::firstOrCreate(
                ['file_name' => $packet->file_name],
                ['created_at' => $packet->created_at]
            );
            return $packet;
        }

        return null;
    }

    /**
     * Tests if a packet can be extracted from the message.
     *
     * @param string $text
     * @return boolean
     */
    public static function isPacket(string $text): bool
    {
        $match = self::matchPacket($text);

        if (null === $match) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Matches packet data.
     *
     * @param string $text
     * @return string|null
     */
    public static function matchPacket(string $text): string|null
    {
        $matches = [];

        if (preg_match(self::PACKET_MASK, $text, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract packet data from the text.
     * [$number, $gets, $size, $fileName]
     *
     * @param string $text
     * @return array|null
     */
    public static function extract(string $text): array|null
    {
        $matches = [];
        preg_match(self::PACKET_MASK, $text, $matches);
        $fileName = (isset($matches[4])) ? $matches[4]: null;
        return [$matches[1], $matches[2], $matches[3], $fileName];
    }

    /**
     * Returns an array of data fields to update.
     *
     * @param string $fileName
     * @param string $size
     * @param integer $gets
     * @param integer $number
     * @param Network $network
     * @param Channel $channel
     * @param Bot $bot
     * @return array
     */
    public static function getDataToUpdate(string $fileName, string $size, int $gets, int $number, Bot $bot, Channel $channel = null): array
    {
        $meta = [];
        $guesser = new MediaTypeGuesser($fileName);
        $mediaType = $guesser->guess();

        if (isset(self::MEDIA_MAP[$mediaType])) {
            $mediaClass = self::MEDIA_MAP[$mediaType];

            try {
                $media = new $mediaClass($fileName);
                $meta = $media->toArray();
            } catch(Exception $e) {
                Log::warning($e);
            }
        }

        $dataToUpdate = ['file_name' => $fileName, 'gets' => $gets, 'size' => $size, 'media_type' => $mediaType, 'meta' => $meta];

        # Check to see if a different file has previously filled this position.
        # Sometimes the bot owner can change which file is being served on this packet number.
        $existingPacket = Packet::where([
            ['number', '=', $number],
            ['network_id', '=', $bot->network->id],
            ['channel_id', '=', $channel->id],
            ['bot_id', '=', $bot->id]
        ])->first();

        # If the packet existing at this location, is not the same file, update the created_at field.
        if (null !== $existingPacket && trim($existingPacket->file_name) !== trim($fileName)) {
            $dataToUpdate['created_at'] = now();
        }

        return $dataToUpdate;
    }

    /**
     * Makes a best guess at which channel a Bot may represent in the absence of a channel name.
     *
     * @param Bot $bot
     * @return Channel
     */
    public static function getBotChannelByBestGuess(Bot $bot): Channel
    {
        // Use the same channel of a packet that was last reported by this bot.
        $packet = Packet::where('bot_id', $bot->id)->orderBy('id', 'DESC')->first();

        if (null !== $packet) {
            return $packet->channel;
        }

        // If this bot has not reported any packets, just pick the last channel reported on this network.
        $packet = Packet::where('network_id', $bot->network->id)->orderBy('id', 'DESC')->first();

        if (null !== $packet) {
            return $packet->channel;
        }

        // If still nothing qualifies just pick a channel on this network.
        $channel = Channel::where('network_id', $bot->network->id)->first();

        return $channel;
    }

    /**
     * Clean's the text to remove any unwanted ascii characters,
     * and remove redundant spaces.
     *
     * @param string $text
     * @return string
     */
    public static function cleanMessage(string $text): string
    {
        // Removes redundant spaces.
        $text = preg_replace("/\s+/", ' ', $text);

        // Removes control characters from string.
        $text = preg_replace('/[\x00-\x1F\x7F]/', '', $text);

        return $text;
    }

}
