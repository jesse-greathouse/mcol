<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue,
    Illuminate\Foundation\Bus\Dispatchable,
    Illuminate\Foundation\Queue\Queueable,
    Illuminate\Queue\InteractsWithQueue,
    Illuminate\Queue\SerializesModels,
    Illuminate\Support\Facades\Log;

use App\Exceptions\DirectoryCreateFailedException,
    App\Exceptions\TransferDownloadFileNotFoundException,
    App\Media\TransferManager,
    App\Media\Service\Plex,
    App\Models\DownloadDestination;

use Exception;

/**
 * Handles the transfer of downloaded media files.
 */
class TrasferDownloadedMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Define the default directories for Windows systems
    const DEFAULT_WINDOWS_VAR_DIR = '%APPDATA%' .  DS . 'var';

    // Define the default directories for Unix-like systems
    const DEFAULT_UNIX_LIKE_VAR_DIR = '$HOME' .  DS . 'var';

    /**
     * The Download object representing the destination for the download.
     *
     * @var DownloadDestination
     */
    public DownloadDestination $downloadDestination;

    /**
     * Create a new job instance.
     *
     * @param DownloadDestination $downloadDestination
     */
    public function __construct(DownloadDestination $downloadDestination)
    {
        $this->downloadDestination = $downloadDestination;
    }

    /**
     * Execute the job.
     *
     * Attempts to transfer the downloaded file to its final destination and performs
     * additional tasks such as scanning for media in Plex if applicable.
     *
     * @param Plex $plex
     * @return void
     */
    public function handle(Plex $plex): void
    {
        $download = $this->downloadDestination->download;

        // Check if the file exists before proceeding
        if (!file_exists($download->file_uri)) {
            $this->handleFileNotFound($download->file_uri);
            return;
        }

        // Sometimes this job can be initiated when the download is still marked as pending.
        // e.g., the file is small so the download completes before the status changes.
        // This step ensures that it gets put in the proper state before it proceeds.
        $this->markDownloadAsIncomplete();

        try {
            $tmpDir = $this->getTmpDir();
            $this->transferFile($download->file_uri, $tmpDir);

            $this->markDownloadAsCompleted();

            // Perform Plex media scan if configured and relevant media types are enabled
            $this->scanMediaWithPlex($plex, $download);
        } catch (Exception $e) {
            // Log and fail the job if an exception occurs during the transfer
            $this->fail($e);
            Log::warning($e);
        }
    }

    /**
     * Marks the download destination as incomplete and saves the status.
     *
     * @return void
     */
    protected function markDownloadAsIncomplete(): void
    {
        $this->downloadDestination->status = DownloadDestination::STATUS_INCOMPLETE;
        $this->downloadDestination->save();
    }

    /**
     * Ensures the temporary directory for file transfer exists and returns its path.
     *
     * @return string
     * @throws DirectoryCreateFailedException
     */
    protected function getTmpDir(): string
    {
        $varDir = env('VAR', $this->getDefaultVarDir());
        $tmpDir = "$varDir/transfer";

        // Ensure the temporary directory exists
        if (!is_dir($tmpDir)) {
            if (!mkdir($tmpDir, 0777, true)) {
                throw new DirectoryCreateFailedException("$tmpDir could not be created.");
            }
        }

        return $tmpDir;
    }

    /**
     * Transfers the file using the TransferManager.
     *
     * @param string $fileUri
     * @param string $tmpDir
     * @return void
     */
    protected function transferFile(string $fileUri, string $tmpDir): void
    {
        $options = ['tmp_dir' => $tmpDir];
        $manager = new TransferManager($fileUri, $this->downloadDestination->destination_dir, $options);
        $manager->transfer();
    }

    /**
     * Marks the download destination as completed and saves the status.
     *
     * @return void
     */
    protected function markDownloadAsCompleted(): void
    {
        $this->downloadDestination->status = DownloadDestination::STATUS_COMPLETED;
        $this->downloadDestination->save();
    }

    /**
     * Scans the media using Plex if configured and relevant media types are enabled.
     *
     * @param Plex $plex
     * @param $download
     * @return void
     */
    protected function scanMediaWithPlex(Plex $plex, $download): void
    {
        if ($plex->isConfigured()) {
            $type = $download->packet->media_type;
            if (in_array($type, $plex->getEnabledMediaTypes())) {
                $plex->scanMediaLibrary($type);
            }
        }
    }

    /**
     * Handles the scenario when the file is not found.
     *
     * @param string $fileUri
     * @return void
     */
    protected function handleFileNotFound(string $fileUri): void
    {
        $e = new TransferDownloadFileNotFoundException(
            "Job attempted to transfer: \"{$fileUri}\" but file no longer exists."
        );
        $this->fail($e);
        Log::warning($e->getMessage());
    }

    /**
     * Get the unique ID for the job.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return (string) $this->downloadDestination->id;
    }

    /**
     * Determines the appropriate download directory based on the operating system or environment.
     *
     * @return string The var directory path.
     */
    private function getDefaultVarDir(): string
    {
        // Default to Windows or Linux/macOS based on the system
        return (PHP_OS_FAMILY === 'Windows')
            ? $this->replaceSystemVariables(self::DEFAULT_WINDOWS_VAR_DIR)
            : $this->replaceSystemVariables(self::DEFAULT_UNIX_LIKE_VAR_DIR);
    }

    /**
     * Replaces system-specific variables with their actual values.
     *
     * @param string $path The path containing system variables.
     * @return string The path with system variables replaced.
     */
    private function replaceSystemVariables(string $path): string
    {
        // Replace %APPDATA% and $HOME with the respective system values
        if (PHP_OS_FAMILY === 'Windows') {
            $path = str_replace('%APPDATA%', getenv('APPDATA'), $path);
        } elseif (PHP_OS_FAMILY === 'Linux' || PHP_OS_FAMILY === 'Darwin') {
            $path = str_replace('$HOME', getenv('HOME'), $path);
        }

        return $path;
    }
}
