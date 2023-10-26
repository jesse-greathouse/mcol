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

        [$number, $gets, $size, $fileName] = $this->extractPacket($message);

        $bot = Bot::updateOrCreate(
            [ 'network_id' => $network->id, 'nick' => $botName ]
        );

        $packet = Packet::updateOrCreate(
            ['number' => $number, 'network_id' => $network->id, 'channel_id' => $channel->id, 'bot_id' => $bot->id],
            ['file_name' => $fileName, 'gets' => $gets, 'size' => $size]
        );

        return $packet;
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
            return [$matches[1], $matches[2], $matches[3], $matches[4]];
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
}
