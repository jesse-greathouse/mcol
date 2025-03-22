<?php

namespace App\Dcc;

use Illuminate\Support\Facades\Log;

use App\Exceptions\HostRefusedConnectionException,
    App\Exceptions\InitializeDownloadStreamException,
    App\Jobs\DccDownload,
    App\Jobs\CheckFileDownloadCompleted,
    App\Models\Bot,
    App\Models\Download,
    App\Models\FileDownloadLock,
    App\Models\Packet;

use DateTime,
    Exception;

// Define DS constant for cross-platform compatibility if not already defined
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

class Client
{
    const CHUNK_BYTES = 2048;
    const UPDATE_INTERVAL = 10; // 10 seconds
    const PACKET_LIST_MASK = '/mylist\.txt$/i';
    const TRANSFER_TERMINATED_MESSAGE = 'TRANSFER TERMINATED';
    const CONNECTION_REFUSED = 111;

    // Define system-specific subdirectories for downloads and packet lists
    const DOWNLOAD_URI = DS . 'var' . DS . 'download';
    const PACKET_LIST_URI =  DS . 'var' . DS . 'packet-lists';

    // Define the default directories for Windows systems
    const DEFAULT_WINDOWS_DOWNLOAD_DIR = '%APPDATA%' .  self::DOWNLOAD_URI;
    const DEFAULT_WINDOWS_PACKET_LIST_DIR = '%APPDATA%' . self::PACKET_LIST_URI;

    // Define the default directories for Unix-like systems
    const DEFAULT_UNIX_LIKE_DOWNLOAD_DIR = '$HOME' . self::DOWNLOAD_URI;
    const DEFAULT_UNIX_LIKE_PACKET_LIST_DIR = '$HOME' . self::PACKET_LIST_URI;

    /** @var int Timestamp of last update. */
    protected ?int $lastUpdate = null;

    /** @var Packet packet related to downloading file. */
    protected Packet $packet;

    /** @var Bot bot from which the file is downloading. */
    protected Bot $bot;

    /** @var string File name of the file to be downloaded. */
    protected string $fileName;

    /** @var int Size of the file in bytes */
    protected int $fileSize;

    /**
     * Create a new job instance.
     */
    public function __construct(string $fileName, int $fileSize, Bot $bot, Packet $packet)
    {
        $this->fileName = $fileName;
        $this->fileSize = $fileSize;
        $this->bot = $bot;
        $this->packet = $packet;
    }

    /**
     * Opens a connection for downloading a file.
     *
     * @param string $host The server host.
     * @param string $port The server port.
     * @param int|null $resume The byte position to resume the download from.
     * @return void
     * @throws InitializeDownloadStreamException If an error occurs during the socket connection or download directory is invalid.
     */
    public function download(string $host, string $port, ?int $resume = null): void
    {
        $bytes = $resume ?? 0;
        $isPacketList = $this->isPacketList();

        try {
            $uri = $this->initializeDownload($isPacketList, $bytes);

            // Attempt to create a socket connection for downloading
            $dlStream = @stream_socket_client("tcp://$host:$port", $errno, $errstr);
            if (!is_resource($dlStream)) {
                $message = "Connection to [$host:$port]: (#$errno): $errstr";

                if (isset($errno) && $errno === self::CONNECTION_REFUSED) {
                    throw new HostRefusedConnectionException($message);
                }

                throw new InitializeDownloadStreamException($message);
            }

            $file = fopen($uri, 'a');
            fseek($file, $bytes);

            while (!feof($dlStream) && file_exists($uri)) {
                if (($chunk = fgets($dlStream, self::CHUNK_BYTES)) === false) break;
                fwrite($file, $chunk);

                if (!$isPacketList && file_exists($uri) && $this->shouldUpdate()) {
                    // clearstatcache removes status caching for filesize on this file
                    // https://www.php.net/manual/en/function.clearstatcache.php
                    clearstatcache(true, $uri);
                    $this->registerDownload($uri, filesize($uri));
                }
            }

            fclose($file);
            fclose($dlStream);

            $this->finalizeDownload($host, $port, $uri);

        } catch (Exception $e) {
            // Log the error
            Log::error($e->getMessage());

            // Ensure any file lock is released before rethrowing
            if ($this->isFileDownloadLocked($this->packet->file_name)) {
                $this->releaseLock($this->packet->file_name);
            }

            throw $e;
        }
    }

    /**
     * Determines whether to update the progress counter.
     *
     * @return bool True if the downloading file status should be updated.
     */
    protected function shouldUpdate(): bool
    {
        $now = time();
        if ($this->lastUpdate === null || ($now - $this->lastUpdate) >= self::UPDATE_INTERVAL) {
            $this->lastUpdate = $now;
            return true;
        }
        return false;
    }

    /**
     * Initializes the download process by setting up directories and file paths.
     *
     * @param bool $isPacketList Determines whether the download is a packet list.
     * @param int $bytes The number of bytes to resume from, passed by reference.
     * @return string The URI of the file to be downloaded.
     * @throws InitializeDownloadStreamException If the download directory doesn't exist.
     */
    protected function initializeDownload(bool $isPacketList, int $bytes): string
    {
        // Choose the appropriate packet list directory
        if ($isPacketList) {
            $packetListDir = $this->getPacketListDirectory();
            if (!is_dir($packetListDir)) {
                if (!mkdir($packetListDir, 0777, true)) {
                    throw new InitializeDownloadStreamException("Packet List directory: $packetListDir could not be created.");
                }
            }
            return $packetListDir . DS . "{$this->bot->id}.txt";
        }

        // Use the environment variable for download directory, or default based on the system
        $downloadDir = env('DOWNLOAD_DIR', $this->getDownloadDirectory());

        if (!is_dir($downloadDir)) {
            if (!mkdir($downloadDir, 0777, true)) {
                throw new InitializeDownloadStreamException("Download directory: $downloadDir could not be created.");
            }
        }

        if (!$this->isFileDownloadLocked()) {
            $this->lockFile();
        }

        $uri = $downloadDir . DS . $this->packet->file_name;

        // If the file exists and it's not a resume, just delete it.
        if (file_exists($uri) && $bytes === 0) {
            unlink($uri);
            touch($uri);
        }

        $this->registerDownload($uri, $bytes);

        return $uri;
    }

    /**
     * Determines the appropriate download directory based on the operating system or environment.
     *
     * @return string The download directory path.
     */
    protected function getDownloadDirectory(): string
    {
        // If the VAR environment variable is set, use it, otherwise default based on the OS
        $envDownloadDir = env('VAR');
        if ($envDownloadDir) {
            return $envDownloadDir  . DS . 'download';
        }

        // Default to Windows or Linux/macOS based on the system
        return (PHP_OS_FAMILY === 'Windows')
            ? $this->replaceSystemVariables(self::DEFAULT_WINDOWS_DOWNLOAD_DIR)
            : $this->replaceSystemVariables(self::DEFAULT_UNIX_LIKE_DOWNLOAD_DIR);
    }

    /**
     * Retrieves the appropriate packet list directory based on the operating system.
     *
     * @return string The packet list directory path.
     */
    protected function getPacketListDirectory(): string
    {
        // If VAR is set, we use it for packet list directory as well
        $envPacketListDir = env('VAR');
        if ($envPacketListDir) {
            return $envPacketListDir . DS . 'packet-lists';
        }

        // Default to Unix-like systems (Linux/macOS) or Windows
        return (PHP_OS_FAMILY === 'Windows')
            ? $this->replaceSystemVariables(self::DEFAULT_WINDOWS_PACKET_LIST_DIR)
            : $this->replaceSystemVariables(self::DEFAULT_UNIX_LIKE_PACKET_LIST_DIR);
    }

    /**
     * Replaces system-specific variables with their actual values.
     *
     * @param string $path The path containing system variables.
     * @return string The path with system variables replaced.
     */
    protected function replaceSystemVariables(string $path): string
    {
        // Replace %APPDATA% and $HOME with the respective system values
        if (PHP_OS_FAMILY === 'Windows') {
            $path = str_replace('%APPDATA%', getenv('APPDATA'), $path);
        } elseif (PHP_OS_FAMILY === 'Linux' || PHP_OS_FAMILY === 'Darwin') {
            $path = str_replace('$HOME', getenv('HOME'), $path);
        }

        return $path;
    }

    /**
     * Finalizes the download, determining success or resumption.
     *
     * @param string $host The server host.
     * @param string $port The server port.
     * @param string $uri The location of the downloaded file.
     * @return void
     */
    protected function finalizeDownload(string $host, string $port, string $uri): void
    {
        clearstatcache(true, $uri);
        $bytesDownloaded = file_exists($uri) ? filesize($uri) : 0;

        if ($bytesDownloaded === $this->fileSize) {
            Download::where('file_name', $this->packet->file_name)->update(['status' => Download::STATUS_COMPLETED, 'progress_bytes' => null]);
        } else {
            $this->handleIncompleteDownload($host, $port, $uri);
        }
    }

    /**
     * Handles an incomplete download scenario, attempting resumption if possible.
     * @param string $host The server host.
     * @param string $port The server port.
     * @param string $uri The location of the downloaded file.
     * @return void
     */
    protected function handleIncompleteDownload(string $host, string $port, string $uri): void
    {
        $download = Download::where('file_name', $this->packet->file_name)->first();
        $download?->delete();
        $this->releaseLock();

        if (!$this->bot || !file_exists($uri)) {
            Log::warning("Unable to resume download: {$this->fileName}");
            return;
        }

        $position = filesize($uri);
        DccDownload::dispatch($host, $port, $this->fileName, $this->fileSize, $this->bot->nick, $position)->onQueue('download');
        Log::warning("Queued to resume download: {$this->fileName} at $position bytes");
    }

    /**
     * Registers or updates the file download status.
     *
     * @param string $uri The URI of the file being downloaded.
     * @param int $bytes The number of bytes downloaded so far.
     * @return Download The created or updated Download instance.
     */
    protected function registerDownload(string $uri, int $bytes): Download
    {
        return Download::updateOrCreate(
            ['file_uri' => $uri],
            [
                'file_name' => $this->packet->file_name,
                'media_type' => $this->packet->media_type,
                'packet_id' => $this->packet->id,
                'meta' => $this->packet->meta,
                'status' => Download::STATUS_INCOMPLETE,
                'file_size_bytes' => $this->fileSize,
                'progress_bytes' => $bytes,
            ]
        );
    }

    /**
     * Releases the lock on the specified file to allow other processes to access it.
     *
     * This method deletes the file's lock entry from the `FileDownloadLock` model, enabling other processes
     * to download or manipulate the file.
     *
     * @return void
     */
    protected function releaseLock(): void
    {
        FileDownloadLock::where('file_name', $this->packet->file_name)->delete();
    }

    /**
     * Determines if the given file name corresponds to a packet list.
     *
     * This method uses a regular expression to check if the provided file name matches the pattern
     * defined by the constant `PACKET_LIST_MASK`, which identifies packet list files.
     *
     * @return bool True if the file name matches the packet list pattern, otherwise false.
     */
    protected function isPacketList(): bool
    {
        return (bool) preg_match(self::PACKET_LIST_MASK, $this->fileName);
    }

    /**
     * Determines if a file is currently locked for downloading.
     *
     * This method checks the `FileDownloadLock` model to see if there is an active lock entry for the specified
     * file, indicating that the file is currently being downloaded or is in use.
     *
     * @return bool True if the file is locked for downloading, otherwise false.
     */
    protected function isFileDownloadLocked(): bool
    {
        return FileDownloadLock::where('file_name', $this->packet->file_name)->exists();
    }


    /**
     * Locks the specified file for downloading to prevent concurrent downloads.
     *
     * This method creates a lock entry in the `FileDownloadLock` model for the specified file, preventing other
     * processes from downloading the same file simultaneously. It also dispatches a job to check if the download is
     * completed after a delay.
     *
     * @return void
     */
    protected function lockFile(): void
    {
        FileDownloadLock::create(['file_name' => $this->packet->file_name]);

        $timestamp = new DateTime();
        CheckFileDownloadCompleted::dispatch($this->packet->file_name, $timestamp)
            ->delay(now()->addMinutes(CheckFileDownloadCompleted::SCHEDULE_INTERVAL));
    }
}
