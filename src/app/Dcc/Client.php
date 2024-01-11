<?php

namespace App\Dcc;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

use App\Exceptions\IllegalPacketException,
    App\Models\Bot,
    App\Models\Download,
    App\Models\Packet,
    App\Models\Network;

class Client
{
    const CHUNK_BYTES = 2048;

    const UPDATE_INTERVAL = 15; # 15 seconds

    /**
     * timestamp of last update.
     *
     * @var integer
     */
    protected $lastUpdate;

    /**
     * Console for using this client
     *
     * @var Command
     */
    protected $console;

    public function __construct(Command $console) {
        $this->console = $console;
    }

    /**
     * Opens a connection for downloading a file.
     *
     * @param string $host
     * @param string $port
     * @param string $fileName
     * @param int|null $fileSize
     * @param string|null $botId
     * @param int|null $resume
     * @return void
     */
    public function open(string $host, string $port, string $fileName, int $fileSize = null, $botId = null, int $resume = null): void
    {
        $bytes = 0;
        $downloadDir = env('DOWNLOAD_DIR', '/var/download');
        $uri = "$downloadDir/$fileName";
        
        $packet = Packet::where('file_name', $fileName)->where('bot_id', $botId)->OrderByDesc('created_at')->first();
        if (!$packet) {
            throw new IllegalPacketException("Packet with bot id: $botId and file: $fileName were expected but not found");
        }

        if (file_exists($uri)) {
            if (null === $resume) {
                unlink($uri);
                touch($uri);
            } else {
                $bytes = $resume;
            }
        }
 
        // Register or update the file download status data.
        $download = $this->registerDownload($uri, $packet->id, $fileSize, $bytes);

        // Open a stream to the remote file system.
        $fp = stream_socket_client("tcp://$host:$port", $errno, $errstr);
        if (!$fp) {
            $this->console->error("$errstr ($errno)");
        } else {
            // Open a file pointer to recieve the file
            // and set the pointer to the correct position.
            $file = fopen($uri, 'a');
            fseek($file, $bytes);

            $download->status = Download::STATUS_INCOMPLETE;
            $download->save();
    
            while (!feof($fp)) {
                $chunk = fgets($fp, self::CHUNK_BYTES);
                $download->progress_bytes += self::CHUNK_BYTES;
                fwrite($file, $chunk);

                // Only save the progress every n intervals for performance.
                if ($this->shouldUpdate()) {
                    $download->status = Download::STATUS_INCOMPLETE;
                    $download->save();
                }
            }

            // If expected file size wasn't sent as a parameter, 
            // it's impossible to know how large the file should be.
            if (null === $fileSize) { 
                $fileSize = $download->progress_bytes;
            }

            $meta = stream_get_meta_data($file);

            if (isset($meta['uri'])) {
                $bytesDownloaded = filesize($meta['uri']);
                if ((integer) $bytesDownloaded === (integer) $fileSize) {
                    $download->status = Download::STATUS_COMPLETED;
                    $download->progress_bytes = null;
                    $download->file_size_bytes = null;
                    $download->save();
                }
            } else {
                $dir = env('DIR', '/usr');
                $src = env('SRC', '/usr/src');
                $bin = "$dir/bin";
                $network = Network::where('id', $packet->network_id)->first();
                $bot = Bot::where('id', $packet->bot_id)->first();
                $command = "XDCC SEND $packet->number";
                $this->console->info("Download of stream: \"$fileName\" closed prematurely.");

                if (null !== $network && null !== $bot) {
                    $this->console->info("Queueing for resume (at $download->progress_bytes of $fileSize )");

                    $console = $this->console;
                    Process::path($src)->start("$bin/php artisan mcol:chat $network->name '$bot->nick' '$command'", function (string $type, string $output) use ($console, $download) {
                        $download->queued_status = 0;
                        $download->save();
                        $console->info("Command $type output: $output");
                    });
                } else {
                    $this->console->info("Unable to Queue for resume because of incomplete network or bot data.");
                }
            }

            fclose($file);
            fclose($fp);
        }
    }

    /**
     * Should the client update the progress counter.
     *
     * @return boolean
     */
    public function shouldUpdate(): bool
    {
        $now = time();
        
        if (null === $this->lastUpdate) {
            $this->lastUpdate = $now;
            return true;
        }

        $interval = $now - $this->lastUpdate;

        if ($interval >= self::UPDATE_INTERVAL) {
            $this->lastUpdate = $now;
            return true;
        }

        return false;
    }

    protected function registerDownload(string $uri,  int $packetId, int $fileSize = null, int $bytes = null): Download
    {
        return Download::updateOrCreate(
            [ 'file_uri' => $uri, 'packet_id' => $packetId ],
            [ 'status' => Download::STATUS_INCOMPLETE, 'file_size_bytes' => $fileSize, 'progress_bytes' => $bytes,  ]
        );
    }

    /**
     * Removes all Non-numeric characters from a string.
     *
     * @param string $txtStr
     * @return string
     */
    public function clnNumericStr(string $txtStr): string
    {
        return preg_replace("/[^0-9]/", "", $txtStr);
    }
}
