<?php

namespace App\Dcc;

use Illuminate\Support\Facades\Log;

use App\Exceptions\IllegalPacketException,
    App\Jobs\DccDownload,
    App\Models\Bot,
    App\Models\Download,
    App\Models\FileDownloadLock,
    App\Models\Packet,
    App\Models\Network;

use \Exception;

class Client
{
    const CHUNK_BYTES = 2048;
    const UPDATE_INTERVAL = 10; // 10 seconds
    const PACKET_LIST_MASK = '/mylist\.txt$/i';
    const TRANSFER_TERMINATED_MESSAGE = 'TRANSFER TERMINATED';

    /**
     * timestamp of last update.
     *
     * @var integer
     */
    protected $lastUpdate;

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
        $isPacketList = $this->isPacketList($fileName);
        $bot = Bot::find($botId);

        if ($isPacketList) {
            $varDir = env('VAR', '/var');
            $downloadDir = "$varDir/packet-lists";
            if (!is_dir($downloadDir)) {
                mkdir($downloadDir);
            }

            $uri = "$downloadDir/$botId.txt";

            if (file_exists($uri)) {
                unlink($uri);
                touch($uri);
            }
        } else {
            $downloadDir = env('DOWNLOAD_DIR', '/var/mcol/download');
            $packet = $this->getPacketByFileNameAndBotId($fileName, $botId);
            if (null === $packet) {
                throw new IllegalPacketException("Packet with bot id: $botId and file: $fileName was expected but not found");
            }

            // Sometimes the bot mutates the file name.
            // This keeps the file name consistent with what's in our records.
            $fileName = $packet->file_name;

            $uri = "$downloadDir/$fileName";

            // Register or update the file download status data.
            $download = $this->registerDownload($uri, $fileName, $packet->media_type, $packet->id, $packet->meta, $fileSize, $bytes);

            if (file_exists($uri)) {
                if (null === $resume) {
                    unlink($uri);
                    touch($uri);
                } else {
                    $bytes = $resume;
                }
            }
        }

        // Open a stream to the remote file system.
        $fp = stream_socket_client("tcp://$host:$port", $errno, $errstr);
        if (!$fp) {
            Log::error("$errstr ($errno)");
        } else {
            // Open a file pointer to recieve the file
            // and set the pointer to the correct position.
            $file = fopen($uri, 'a');
            fseek($file, $bytes);

            // Reading ends when length - 1 bytes have been read
            // Adds +1 to byte length so added bytes can be tracked more evenly.
            // bytes + chunk = downloaded progress.
            $increment = self::CHUNK_BYTES + 1;

            while (!feof($fp)) {
                // Sometimes user deletes file before it's finished :-(
                // Silly Users >:@
                if (!file_exists($uri)) {
                    break;
                }

                $chunk = fgets($fp, $increment);
                if (false === $chunk) break;
                fwrite($file, $chunk);

                // Only save the progress every n intervals for performance.
                if (!$isPacketList && file_exists($uri) && $this->shouldUpdate()) {
                    clearstatcache(true, $uri); // clears the caching of filesize
                    $progressSize = fileSize($uri);
                    $download = $this->registerDownload($uri, $fileName, $packet->media_type, $packet->id, $packet->meta, $fileSize, $progressSize);
                }
            }

            // Packet lists can bail now.
            if ($isPacketList) {
                fclose($file);
                fclose($fp);
                Log::warning("Downloaded the packet list from {$bot->nick}");
                return;
            }

            // If expected file size wasn't sent as a parameter,
            // it's impossible to know how large the file should be.
            if (null === $fileSize) {
                $fileSize = $download->progress_bytes;
            }

            $meta = stream_get_meta_data($file);

            if (isset($meta['uri']) && file_exists($uri)) {
                clearstatcache(true, $meta['uri']); // clears the caching of filesize
                $bytesDownloaded = filesize($meta['uri']);
                if ((integer) $bytesDownloaded === (integer) $fileSize) {
                    $download->status = Download::STATUS_COMPLETED;
                    $download->progress_bytes = null;
                    $download->save();
                }
            } else {
                // Delete the download and release the lock.
                $download->delete();
                $this->releaseLock($fileName);
                $network = Network::where('id', $packet->network_id)->first();
                Log::warning("Download of stream: \"$fileName\" closed prematurely.");

                // Attempt Resume or Bail
                if (null !== $network && null !== $bot && file_exists($uri)) {
                    $position = filesize($uri);
                    DccDownload::dispatch($host, $port, $fileName, $fileSize, $bot->nick, $position)->onQueue('download');
                    Log::warning("Queued to resume DCC Download Job: host: $host port: $port file: $fileName file-size: $fileSize bot: '{$bot->nick}' resume: $position");
                } else {
                    Log::warning("Unable to Queue for resume of: \"$fileName\" because of missing file or incomplete network/bot data.");
                }
            }

            fclose($file);
            fclose($fp);

            Log::warning(self::TRANSFER_TERMINATED_MESSAGE . ": $fileName");
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

    /**
     * Set up the download object
     *
     * @param string $uri
     * @param string $fileName
     * @param string $mediaType
     * @param integer $packetId
     * @param array $meta
     * @param integer|null $fileSize
     * @param integer|null $bytes
     * @return Download
     */
    protected function registerDownload(string $uri,  string $fileName, string $mediaType, int $packetId, array $meta, int $fileSize = null, int $bytes = null): Download
    {
        return Download::updateOrCreate(
            [ 'file_uri' => $uri ],
            [
                'file_name'         => $fileName,
                'media_type'        => $mediaType,
                'packet_id'         => $packetId,
                'meta'              => $meta,
                'status'            => Download::STATUS_INCOMPLETE,
                'file_size_bytes'   => $fileSize,
                'progress_bytes'    => $bytes,
                'queued_total'      => null,
                'queued_status'     => null,
            ]
        );
    }

    /**
     * Removes all Non-numeric characters from a string.
     *
     * @param string $txtStr
     * @return string
     */
    protected function clnNumericStr(string $txtStr): string
    {
        return preg_replace("/[^0-9]/", "", $txtStr);
    }

    /**
     * Returns a single Download model instance.
     *
     * @param string $fileName
     * @return void
     */
    protected function releaseLock(string $fileName): void
    {
        $lock = FileDownloadLock::where('file_name', $fileName)->first();
        if (null !== $lock) {
            $lock->delete();
        } else {
            Log::warning("Attempted download lock removal of: $fileName, failed. Lock did not exist.");
        }
    }

    /**
     * Answers if a file is a packet list by the file name.
     *
     * @param string $fileName
     * @return bool
     */
    protected function isPacketList(string $fileName): bool
    {
        $matches = [];
        preg_match(self::PACKET_LIST_MASK, $fileName, $matches);

        return (0 < count($matches));
    }

    /**
     * From a given file name, retrieve a packet object.
     *
     * @param string $fileName
     * @param int $botId
     * @return Packet|null
     */
    protected function getPacketByFileNameAndBotId(string $fileName, int $botId): Packet | null
    {
        $packet = null;

        try {
            $packet = Packet::where('file_name', $fileName)->where('bot_id', $botId)->OrderByDesc('created_at')->first();

            if (null === $packet) {
                // Sometimes the Bot advertize a file having whitespace delimiter, but then sends the file with _ (underscore) delimiter.
                // It makes me very happy that some bot operators do this :-D because now I get to write this extra line of code.
                $underscoreFileName = str_replace('_', ' ', $fileName);
                $packet = Packet::where('file_name', $underscoreFileName)->where('bot_id', $botId)->OrderByDesc('created_at')->first();
            }
        } catch(Exception $e) {
            Log::error("Failed to Query packet by file: $fileName and botId: $botId");
            Log::error("$e");
        }

        return $packet;
    }
}
