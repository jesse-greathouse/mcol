<?php

namespace App\Jobs;

use App\Dcc\Client;
use App\Exceptions\HostRefusedConnectionException;
use App\Exceptions\IllegalPacketException;
use App\Exceptions\UnknownBotException;
use App\Models\Bot;
use App\Models\Packet;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DccDownload implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     * Two days is the maximum length of time a download can run for now.
     * TODO: Make this more dynamic
     *
     * @var int
     */
    public $timeout = 172800;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 172800;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

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
     * @var string
     */
    protected $botName;

    /**
     * Resume flag.
     *
     * @var int
     */
    protected $resume;

    /**
     * Instance of a bot model.
     *
     * @var Bot
     */
    protected $bot;

    /**
     * Create a new job instance.
     */
    public function __construct(string $host, string $port, string $file, string $fileSize, string $botName, int $resume = 0)
    {
        $this->host = $host;
        $this->port = $port;
        $this->file = $file;
        $this->fileSize = $fileSize;
        $this->botName = $botName;
        $this->resume = $resume;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bot = $this->getBot();
        $packet = $this->getPacketByBot($bot);

        if (! $packet) {
            throw new IllegalPacketException("Packet with bot id: {$bot->id} and file: {$this->file} was expected but not found");
        }

        $dcc = new Client($this->file, $this->fileSize, $bot, $packet);

        try {
            $dcc->download(long2ip($this->host), $this->port, $this->resume);
        } catch (HostRefusedConnectionException $e) {
            Log::warning("DCC Client: Host Refused Connection \nbot: {$bot->nick} \nhost: {$this->host} \nfile: {$this->file} \nmessage: \n{$e->getMessage()}");
            Log::warning("Canceling request from: \nbot: {$bot->nick}");
            CancelRequest::dispatch($bot);
        }
    }

    /**
     * Returns a Bot or null.
     */
    protected function getBot(): ?Bot
    {
        if ($this->bot === null) {

            $bot = Bot::where('nick', $this->botName)->first();

            if ($bot === null) {
                throw new UnknownBotException("Bot with the name: '{$this->botName}' was not found.");
            }

            $this->bot = $bot;
        }

        return $this->bot;
    }

    /**
     * Retrieves a packet based on the file name and bot ID.
     *
     * This method looks for a packet in the database that matches the provided file name and bot ID.
     * It tries to find an exact match for the file name first, and if not found, it attempts to replace
     * underscores in the file name with spaces and searches again.
     *
     * @param  Bot  $bot  the bot associated with the packet.
     * @return Packet|null The found packet, or null if no packet is found for the given file name and bot ID.
     */
    protected function getPacketByBot(Bot $bot): ?Packet
    {
        return Packet::where('file_name', $this->file)->where('bot_id', $bot->id)->orderByDesc('created_at')->first()
                ?? Packet::where('file_name', str_replace('_', ' ', $this->file))->where('bot_id', $bot->id)->orderByDesc('created_at')->first();
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "{$this->botName}_{$this->file}";
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $e)
    {
        Log::warning("DccDownload job failed on file: {$this->file}\n bot: {$this->botName}\n host: {$this->host}\n message: \n{$e->getMessage()}");
    }
}
