<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use Exception,
    App\Dcc\Client,
    App\Exceptions\UnknownBotException,
    App\Jobs\CheckFileDownloadCompleted,
    App\Models\Bot,
    App\Models\FileDownloadLock;

use \DateTime;

class DccDownload implements ShouldQueue, ShouldBeUnique
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
        if (!$this->isFileDownloadLocked()) {
            $this->lockFile();
            //Queue the job that checks if the file is finished downloading.
            $timeStamp = new DateTime('now');
            CheckFileDownloadCompleted::dispatch($this->file, $timeStamp)
                ->delay(now()->addMinutes(CheckFileDownloadCompleted::SCHEDULE_INTERVAL));
        }

        $bot = $this->getBot();
        $dcc = new Client($this);
        $dcc->open(long2ip($this->host), $this->port, $this->file, $this->fileSize, $bot->id, $this->resume);
    }

    /**
     * Returns a Bot or null.
     *
     * @return Bot|null
     */
    protected function getBot(): Bot|null
    {
        if (null === $this->bot) {

            $bot = Bot::where('nick', $this->botName)->first();

            if (null === $bot) {
                throw new UnknownBotException("Bot with the name: '{$this->botName}' was not found.");
            }

            $this->bot = $bot;
        }

        return $this->bot;
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
    public function failed(Exception $exception)
    {
        Log::warning("DccDownload job failed on file: {$this->file}\n bot: {$this->botName}\n host: {$this->host}\n message: \n{$exception->getMessage()}");
    }

    /**
     * Checks to see if there is a download lock on the file name.
     * Download locks prevents a file from being simultanously downloaded from multiple sources.
     *
     * @return boolean
     */
    protected function isFileDownloadLocked(): bool
    {
        $lock = FileDownloadLock::where('file_name', $this->file)->first();

        if (null !== $lock) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Locks a file.
     *
     * @return boolean
     */
    protected function lockFile(): void
    {
        // Lock the file for Downloading to prevent further downloads of the same file.
        FileDownloadLock::create(['file_name' => $this->file]);
    }
}
