<?php

namespace App\Dcc;

use Illuminate\Console\Command;

use App\Models\Download,
    App\Models\Packet;

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
     * @return void
     */
    public function open(string $host, string $port, string $fileName, int $fileSize = null): void
    {
        $bytes = 0;
        $downloadDir = env('DOWNLOAD_DIR', '/var/download');
        $uri = "$downloadDir/$fileName";
        $fp = stream_socket_client("tcp://$host:$port", $errno, $errstr);

        if (file_exists($uri)) {
            $bytes = filesize($uri);
        }

        $file = fopen($uri, 'a');
        $download = $this->registerDownload($uri, $fileName, $fileSize, $bytes);

        if (!$fp) {
            echo "$errstr ($errno)<br />\n";
        } else {
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

            $meta = stream_get_meta_data($file);
            if (isset($meta['uri'])) {
                $download->progress_bytes = filesize($meta['uri']);
            }

            $download->status = Download::STATUS_COMPLETED;
            $download->save();

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

    protected function registerDownload(string $uri, string $fileName, int $fileSize = null, int $bytes = null): Download
    {
        $packet = Packet::where('file_name', $fileName)->first();

        $download = Download::updateOrCreate(
            ['packet_id' => $packet->id],
            ['status' => Download::STATUS_INCOMPLETE, 'file_uri' => $uri, 'file_size_bytes' => $fileSize, 'progress_bytes' => $bytes], 
        );

        return $download;
    }
}
