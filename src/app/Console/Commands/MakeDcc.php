<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Dcc\Client,
    App\Models\Bot,
    App\Models\Packet;

class MakeDcc extends Command
{
    /** @var string|null Host selected for connection */
    protected ?string $host = null;

    /** @var int|null Network port for the connection */
    protected ?int $port = null;

    /** @var string|null Name of the file to be sent */
    protected ?string $file = null;

    /** @var int|null Size of the file in bytes */
    protected ?int $fileSize = null;

    /** @var Bot|null Instance of the bot */
    protected ?Bot $bot = null;

    /** @var string Command signature */
    protected $signature = 'mcol:make-dcc {--host=} {--port=} {--file=} {--file-size=} {--bot=} {--resume=0}';

    /** @var string Command description */
    protected $description = 'Direct Client Connection via stream socket';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $host = $this->getHost();
        $port = $this->getPort();
        $file = $this->getFile();
        $bot = $this->getBot();
        $fileSize = $this->getFileSize();

        if (!$host || !$port || !$file || !$bot) {
            return;
        }

        $packet = $this->getPacketByFileAndBot($file, $bot);
        $resume = $this->option('resume') ?? null;

        (new Client($file, $fileSize, $bot, $packet))
            ->download(long2ip($host), $port, $resume);
    }

    /**
     * Retrieve a Bot instance or null.
     */
    protected function getBot(): ?Bot
    {
        return $this->bot ??= Bot::where('nick', $this->option('bot'))?->first() ?: tap(null, fn() => $this->error('--bot is required.'));
    }

    /**
     * Retrieve the host as a string.
     */
    protected function getHost(): ?string
    {
        return $this->host ??= $this->option('host') ?: tap(null, fn() => $this->error('A valid --host is required.'));
    }

    /**
     * Retrieve the port as an integer.
     */
    protected function getPort(): ?int
    {
        return $this->port ??= filter_var($this->option('port'), FILTER_VALIDATE_INT) ?: tap(null, fn() => $this->error('A valid --port is required.'));
    }

    /**
     * Retrieve the file name as a string.
     */
    protected function getFile(): ?string
    {
        return $this->file ??= $this->option('file') ?: tap(null, fn() => $this->error('A valid --file is required.'));
    }

    /**
     * Retrieve the file size as an integer.
     */
    protected function getFileSize(): ?int
    {
        return $this->fileSize ??= (int) $this->option('file-size');
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
