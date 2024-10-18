<?php

namespace App\Packet;

use App\Jobs\GeneratePacketMeta,
    App\Models\Bot,
    App\Models\Packet,
    App\Models\Channel,
    App\Models\FileFirstAppearance,
    App\Packet\MediaType\MediaTypeGuesser;

class Parse {

    const PACKET_MASK = '/#([0-9]+)\s+([0-9]+)x\s+\[([0-9B-k\.\s]+)\]\s+(.+)/';

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
        $matches = [];
        $message = self::cleanMessage($text);
        preg_match(self::PACKET_MASK, $message, $matches);
        if (5 > count($matches)) return null;

        [, $number, $gets, $size, $fileName] = $matches;

        $channel = (null === $channel) ? self::getBotChannelByBestGuess($bot) : $channel;

        $dataToUpdate = self::getDataToUpdate($fileName, $size, intval($gets), intval($number), $bot, $channel);

        $packet = Packet::updateOrCreate(
            ['number' => $number, 'network_id' => $bot->network->id, 'channel_id' => $channel->id, 'bot_id' => $bot->id],
            $dataToUpdate
        );

        // If the metadata is empty, queue a job for the metadata.
        if (0 >= count($packet->meta)) {
            GeneratePacketMeta::dispatch($packet)->onQueue('meta');
        }

        // Update First Appearance Table if no entry is present.
        FileFirstAppearance::firstOrCreate(
            ['file_name' => $packet->file_name],
            ['created_at' => $packet->created_at]
        );
        return $packet;
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

        $dataToUpdate = ['file_name' => $fileName, 'gets' => $gets, 'size' => $size, 'media_type' => $mediaType, 'meta' => $meta];

        # Check to see if a different file has previously filled this position.
        # Sometimes the bot owner can change which file is being served on this packet number.
        $existingPacket = Packet::where([
            ['number', '=', $number],
            ['network_id', '=', $bot->network->id],
            ['channel_id', '=', $channel->id],
            ['bot_id', '=', $bot->id]
        ])->first();

        if (null !== $existingPacket) {
             # If the packet existing at this location, is not the same file, update the created_at field.
            if (trim($existingPacket->file_name) !== trim($fileName)) {
                $dataToUpdate['created_at'] = now();
            } else{
                // Use the old metadata if is still the same file name.
                $dataToUpdate['meta'] = $existingPacket->meta;
            }
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
