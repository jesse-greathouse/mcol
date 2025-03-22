<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Exceptions\DirectoryCreateFailedException,
    App\Exceptions\TransferDownloadFileNotFoundException,
    App\Exceptions\UnknownDownloadException,
    App\Jobs\CheckDownloadedFileRemoved,
    App\Jobs\TrasferDownloadedMedia,
    App\Media\TransferManager,
    App\Models\Download,
    App\Models\DownloadDestination;

use Exception;

/**
 * Class TransferCompleted
 *
 * Handles the transfer of completed downloads by queuing transfer jobs if pending.
 */
class TransferCompleted extends Command
{
    /**
     * URI of file to be transferred.
     *
     * @var ?string
     */
    protected $uri = null;

    /**
     * The Download object.
     *
     * @var ?Download $download
     */
    protected ?Download $download = null;

    /**
     * Path where the completed transfer will be stored.
     *
     * @var ?string
     */
    protected $destination = null;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:transfer-completed {uri?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queues Transfer jobs on Completed Downloads if pending.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $uri = $this->getUri();

        if (null !== $uri) {
            $download = $this->getDownloadByUri($uri);
            $this->handleTestDownloadDestination($download);
            return;
        }


        // Retrieve all completed downloads
        $downloads = Download::where('status', Download::STATUS_COMPLETED)->get();

        // Process each completed download
        foreach ($downloads as $download) {
            $this->handleDownloadDestination($download);
        }
    }

    protected function handleTestDownloadDestination(Download $download): void
    {
        // Check if a download destination is registered for the completed download
        $downloadDestination = DownloadDestination::where('download_id', $download->id)->first();

        if ($downloadDestination !== null) {
            // Update the status to queued
            $downloadDestination->status = DownloadDestination::STATUS_QUEUED;
            $downloadDestination->save();

            $this->warn("Handling media transfer for: \"{$download->file_uri}\".");

            // Check if the file exists before proceeding
            if (!file_exists($download->file_uri)) {
                $this->handleFileNotFound($download->file_uri);
                return;
            }

            // Mark the download as incomplete
            $downloadDestination->status = DownloadDestination::STATUS_INCOMPLETE;
            $downloadDestination->save();

            try {
                // $tmpDir is used as an intermediate directory for unpacking archives.
                $tmpDir = $this->getTmpDir();
                $this->transferFile($download->file_uri, $tmpDir);

                // Instantiate the file manager and transfer the file.
                $options = ['tmp_dir' => $tmpDir];
                $manager = new TransferManager(
                    $download->file_uri,
                    $downloadDestination->destination_dir,
                    $options
                );

                $manager->transfer();

                // Mark the transfer status complete
                $downloadDestination->status = DownloadDestination::STATUS_COMPLETED;
                $downloadDestination->save();
            } catch (Exception $e) {
                // Log and fail the job if an exception occurs during the transfer
                $this->warn($e);
                return;
            }

            // Schedule a job to check if the file has been removed from the file system
            CheckDownloadedFileRemoved::dispatch($download)
                ->delay(now()->addMinutes(CheckDownloadedFileRemoved::SCHEDULE_INTERVAL));
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
            "Attempted to transfer: \"{$fileUri}\" but file no longer exists."
        );
        $this->warn($e->getMessage());
    }

    /**
     * Handles transferring a file that has a registered destination.
     *
     * Checks if a download destination is waiting for transfer and then queues
     * the transfer job and a file removal check job.
     *
     * @param \App\Models\Download $download
     * @return void
     */
    protected function handleDownloadDestination(Download $download): void
    {
        // Check if a download destination is registered for the completed download
        $downloadDestination = DownloadDestination::where('download_id', $download->id)
            ->where('status', DownloadDestination::STATUS_WAITING)
            ->first();

        if ($downloadDestination !== null) {
            // Update the status to queued
            $downloadDestination->status = DownloadDestination::STATUS_QUEUED;
            $downloadDestination->save();

            // Dispatch the transfer job to the 'transfer' queue
            TrasferDownloadedMedia::dispatch($downloadDestination)
                ->onQueue('transfer');

            $this->warn("Queued Download: \"{$download->file_uri}\" for transfer.");

            // Schedule a job to check if the file has been removed from the file system
            CheckDownloadedFileRemoved::dispatch($download)
                ->delay(now()->addMinutes(CheckDownloadedFileRemoved::SCHEDULE_INTERVAL));
        }
    }

    /**
     * Returns a Download or null.
     *
     * @param string $uri The uri of the file.
     * @return ?Download|null
     */
    protected function getDownloadByUri(string $uri): Download|null
    {
        if (null === $this->download) {
            $download = Download::where('file_uri', $uri)->first();

            if (null === $download) {
                throw new UnknownDownloadException("Download with the file uri: '$uri' was not found.");
            }

            $this->download = $download;
        }

        return $this->download;
    }

    /**
     * Returns the Uri passed by the user.
     *
     * @return ?String
     */
    protected function getUri(): string|null
    {
        if (null === $this->uri) {
            $this->uri = $this->argument('uri');
        }

        return $this->uri;
    }

    /**
     * Ensures the temporary directory for file transfer exists and returns its path.
     *
     * @return string
     * @throws DirectoryCreateFailedException
     */
    protected function getTmpDir(): string
    {
        $varDir = env('VAR', '/var');
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

    }
}
