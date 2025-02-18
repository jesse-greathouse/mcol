<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Dcc\Client,
    App\Models\Bot,
    App\Models\Packet;

class MakeDcc extends Command
{
    /**
     * Host selected for connection
     *
     * @var string
     */
    protected $host;

    /**
     * network selected for run
     *
     * @var string
     */
    protected $port;

    /**
     * Name of file to be sent.
     *
     * @var string
     */
    protected $file;

    /**
     * Name of file size of the file.
     *
     * @var string
     */
    protected $fileSize;

    /**
     * Name of the bot.
     *
     * @var Bot
     */
    protected $bot;

    /**
     * The name of the file.
     *
     * @var string
     */
    protected $signature = 'mcol:make-dcc {--host=} {--port=} {--file=} {--file-size=} {--bot=} {--resume=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Direct Client Connection via stream socket';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $host = $this->getHost();
        if (!$host) $this->error('A valid --host is required.');

        $port = $this->getPort();
        if (!$port) $this->error('A valid --port is required.');

        $file = $this->getFile();
        if (!$file) $this->error('A valid --file is required.');

        $bot = $this->getBot();
        if (!$bot) $this->error('A valid --bot is required.');

        $fileSize = $this->getFileSize();

        $resume = ($this->option('resume')) ? $this->option('resume') : null;

        $packet = $this->getPacketByFileAndBot($file, $bot);

        if (!$host || !$port || !$file || !$bot) return;

        $dcc = new Client($file, $fileSize, $bot, $packet);
        $dcc->download(long2ip($host), $port, $resume);

        return;
    }

    /**
     * Returns a Bot or null.
     *
     * @return Bot|null
     */
    protected function getBot(): Bot|null
    {
        if (null === $this->bot) {
            $nick = $this->option('bot');

            if (null === $nick) {
                $this->error('--bot is required.');
            }

            $bot = Bot::where('nick', $nick)->first();

            if (null !== $bot) {
                $this->bot = $bot;
            }
        }

        return $this->bot;
    }

    /**
     * Returns a string of the host name.
     *
     * @return string|null
     */
    protected function getHost(): string|null
    {
        if (null === $this->host) {
            $host = $this->option('host');

            if (null === $host) {
                $this->error('A valid --host is required.');
            }

            $this->host  = $host;
        }

        return $this->host;
    }

    /**
     * Returns a string of the socket port.
     *
     * @return string|null
     */
    protected function getPort(): string|null
    {
        if (null === $this->port) {
            $port = $this->option('port');

            if (null === $port) {
                $this->error('A valid --port is required.');
            }

            $this->port = $port;
        }

        return $this->port;
    }

    /**
     * Returns a string of the file name.
     *
     * @return string|null
     */
    protected function getFile(): string|null
    {
        if (null === $this->file) {
            $file = $this->option('file');

            if (null === $file) {
                $this->error('A valid --file is required.');
            }

            $this->file = $file;
        }

        return $this->file;
    }

    /**
     * Returns a size of the file.
     *
     * @return int|null
     */
    protected function getFileSize(): int|null
    {
        if (null === $this->fileSize) {
            $fileSize = (integer) $this->option('file-size');

            $this->fileSize = $fileSize;
        }

        return $this->fileSize;
    }

    /**
     * Retrieves a packet based on the file name and bot ID.
     *
     * This method looks for a packet in the database that matches the provided file name and bot ID.
     * It tries to find an exact match for the file name first, and if not found, it attempts to replace
     * underscores in the file name with spaces and searches again.
     *
     * @param string $file the the file to match with the bot.
     * @param Bot $bot the bot associated with the packet.
     *
     * @return Packet|null The found packet, or null if no packet is found for the given file name and bot ID.
     */
    protected function getPacketByFileAndBot(string $file, Bot $bot): ?Packet
    {
        return Packet::where('file_name', $file)->where('bot_id', $bot->id)->orderByDesc('created_at')->first()
                ?? Packet::where('file_name', str_replace('_', ' ', $file))->where('bot_id', $file)->orderByDesc('created_at')->first();
    }
}
