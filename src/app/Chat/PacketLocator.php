<?php

namespace App\Chat;

use App\Models\Bot,
    App\Models\Channel,
    App\Models\Network,
    App\Models\Packet;

class PacketLocator
{
    const PACKET_MASK = '/#([0-9]+)\s+([0-9]+)x\s+\[([0-9B-k\.\s]+)\]\s+(.+)/';

    /**
     * Locates a packet in the Message and returns a Packet Object.
     *
     * @param string $message
     * @param string $botName
     * @param Network $network
     * @param Channel $channel
     * @return Packet|null
     */
    public function locate(string $message, string $botName, Network $network, Channel $channel): Packet|null
    {
        $message = self::cleanMessage($message);

        if (!$this->isPacket($message)) {
            return null;
        }

        $bot = Bot::updateOrCreate(
            [ 'network_id' => $network->id, 'nick' => $botName ]
        );

        [$number, $gets, $size, $fileName] = $this->extractPacket($message);

        if ($fileName) {
            $dataToUpdate = $this->getDataToUpdate($fileName, $size, $gets, $number, $network, $channel, $bot);

            $packet = Packet::updateOrCreate(
                ['number' => $number, 'network_id' => $network->id, 'channel_id' => $channel->id, 'bot_id' => $bot->id],
                $dataToUpdate
            );

            return $packet;
        }

        return null;
    }

    /**
     * Clean's the message to remove any unwanted ascii characters,
     * and remove redundant spaces.
     *
     * @param string $message
     * @return string
     */
    public static function cleanMessage(string $message): string
    {
        // Removes control characters from string.
        $text = preg_replace('/[\x00-\x1F\x7F]/', '', $message);

        // Removes redundant spaces.
        $text = preg_replace("/\s+/", " ", $text);

        return $text;
    }

    /**
     * Extract packet data from the message.
     *
     * @param [type] $text
     * @return array|null
     */
    protected function extractPacket($text): array|null
    {
        $matches = [];
        if (preg_match(self::PACKET_MASK, $text, $matches)) {
    
            $fileName = (isset($matches[4])) ? $this->cleanMessage($matches[4]): null;
    
            return [$matches[1], $matches[2], $matches[3], $fileName];
        }

        return null;
    }

    /**
     * Tests if a packet can be extracted from the message.
     *
     * @param string $text
     * @return boolean
     */
    protected function isPacket(string $text): bool
    {
        $match = $this->matchPacket($text);

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
    protected function matchPacket(string $text): string|null
    {
        $matches = [];

        if (preg_match(self::PACKET_MASK, $text, $matches)) {
            return $matches[1];
        }

        return null;
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
    protected function getDataToUpdate(string $fileName, string $size, int $gets, int $number, Network $network, Channel $channel, Bot $bot): array
    {
        $dataToUpdate = ['file_name' => $fileName, 'gets' => $gets, 'size' => $size];

        # Check to see if a different file has previously filled this position.
        # Sometimes the bot owner can change which file is being served on this packet number.
        $existingPacket = Packet::where([
            ['number', '=', $number],
            ['network_id', '=', $network->id],
            ['channel_id', '=', $channel->id],
            ['bot_id', '=', $bot->id]
        ])->first();

        # If the packet existing at this location, is not the same file, update the created_at field.
        if (null !== $existingPacket && trim($existingPacket->file_name) !== trim($fileName)) {
            $dataToUpdate['created_at'] = now();
        }

        return $dataToUpdate;
    }
}
