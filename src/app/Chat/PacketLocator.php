<?php

namespace App\Chat;

use App\Models\Bot,
    App\Models\Channel,
    App\Models\Network,
    App\Models\Packet;

class PacketLocator
{
    const PACKET_MASK = '/#([0-9]+)\s+([0-9]+)x\s+\[([0-9B-k\.\s]+)\]\s+(.+)/';

    public function locate(string $message, string $botName, Network $network, Channel $channel): Packet|null
    {
        $message = $this->cleanMessage($message);

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

    protected function cleanMessage(string $message): string
    {
        // Removes control characters from string.
        $text = preg_replace('/[\x00-\x1F\x7F]/', '', $message);

        // Removes redundant spaces.
        $text = preg_replace("/\s+/", " ", $text);

        return $text;
    }

    protected function extractPacket($text): array
    {
        $text = preg_replace("/\[/", "", $text);
        $text = preg_replace("/\]/", "", $text);
        $text = preg_replace("/\s+/", " ", $text);
        $parts = explode(' ', $text);
        $number = $this->matchPacket($parts[0]);
        $gets = str_replace('x', '', $parts[1]);
        $size = $parts[2];
        $fileName = $parts[3];
        $fileName = preg_replace('/[\x00-\x1F\x7F]/', '', $fileName);

        return [$number, $gets, $size, $fileName];
    }

    protected function isPacket(string $text): bool
    {
        $match = $this->matchPacket($text);

        if (null === $match) {
            return false;
        } else {
            return true;
        }
    }

    protected function matchPacket(string $text): string|null
    {
        $matches = [];

        if (preg_match('/#([0-9]+)/', $text, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
