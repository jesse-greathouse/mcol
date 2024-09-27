<?php

namespace App\Chat;

use App\Models\Bot,
    App\Models\Channel,
    App\Models\FileFirstAppearance,
    App\Models\Network,
    App\Models\Packet,
    App\Packet\MediaType\MediaTypeGuesser;

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

    /**
     * Removes formatting of Bold
     * https://modern.ircdocs.horse/formatting
     *
     * @param string $message
     * @return string
     */
    public static function cleanBold(string $message): string
    {
        return str_replace('0x02', '', $message);
    }

    /**
     * Removes formatting of Italics
     * https://modern.ircdocs.horse/formatting
     *
     * @param string $message
     * @return string
     */
    public static function cleanItalics(string $message): string
    {
        return str_replace('0x1D', '', $message);
    }

    /**
     * Removes formatting of Underline
     * https://modern.ircdocs.horse/formatting
     *
     * @param string $message
     * @return string
     */
    public static function cleanUnderline(string $message): string
    {
        return str_replace('0x1F', '', $message);
    }

    /**
     * Removes formatting of Strikethrough
     * https://modern.ircdocs.horse/formatting
     *
     * @param string $message
     * @return string
     */
    public static function cleanStrikethrough(string $message): string
    {
        return str_replace('0x1E', '', $message);
    }

    /**
     * Removes formatting of Monospace
     * https://modern.ircdocs.horse/formatting
     *
     * @param string $message
     * @return string
     */
    public static function cleanMonospace(string $message): string
    {
        return str_replace('0x11', '', $message);
    }

    /**
     * Removes formatting of Color
     * https://modern.ircdocs.horse/formatting
     *
     * @param string $message
     * @return string
     */
    public static function cleanColor(string $message): string
    {
        // <CODE><COLOR>,<COLOR> - Set the foreground and background color.
        $text = preg_replace('/0x03\\d{1,2}\\,\\d{1,2}/', '', $message);

        // <CODE><COLOR>, - Set the foreground color and display the , character as text.
        $text = preg_replace('/0x03\\d{1,2}/', '', $text);

        // <CODE>, - Reset foreground and background colors and display the , character as text.
        $text = preg_replace('/0x03/', '', $text);

        return $text;
    }

    /**
     * Removes formatting of Hex Color
     * https://modern.ircdocs.horse/formatting
     *
     * @param string $message
     * @return string
     */
    public static function cleanHexColor(string $message): string
    {
        // <CODE><COLOR>,<COLOR> - Set the foreground and background color.
        $text = preg_replace('/0x04[A-Za-z0-9]{6}\\,[A-Za-z0-9]{1,2}/', '', $message);

        // <CODE><COLOR>, - Set the foreground color and display the , character as text.
        $text = preg_replace('/0x04[A-Za-z0-9]{6}/', '', $text);

        // <CODE>, - Reset foreground and background colors and display the , character as text.
        $text = preg_replace('/0x04/', '', $text);

        return $text;
    }

    /**
     * Removes formatting of G Color
     *
     * @param string $text
     * @return string
     */
    public static function cleanGColor(string $text): string
    {
        $text = preg_replace('/(\|10\s|\|09\s|\|00\s|04\s|\s04\|00\s|04$)/', '$2 ', $text);
        var_dump("replacement: $text");
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
    
            $fileName = (isset($matches[4])) ? $matches[4]: null;
    
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
        $guesser = new MediaTypeGuesser($fileName);
        $mediaType = $guesser->guess();
        $dataToUpdate = ['file_name' => $fileName, 'gets' => $gets, 'size' => $size, 'media_type' => $mediaType];

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
