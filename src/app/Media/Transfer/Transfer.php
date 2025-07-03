<?php

namespace App\Media\Transfer;

use App\Exceptions\TransferFileUriNotFoundException;
use App\FileSystem;
use App\Media\TransferManager;

// Define DS constant for cross-platform compatibility if not already defined
if (! defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 * Abstract class for handling file transfers with a manifest and options.
 */
abstract class Transfer
{
    use FileSystem;

    /**
     * Holds the TransferManager instance.
     */
    protected TransferManager $manager;

    /**
     * Array of options.
     */
    protected array $options = [];

    /**
     * List of files to be transferred, indexed by file name.
     *
     * @var array<string, array{uri: string, name: string, size: int}>
     */
    protected array $manifest = [];

    /**
     * Tracks whether the transfer is completed.
     */
    protected bool $completed = false;

    /**
     * Transfer constructor.
     */
    public function __construct(TransferManager $manager, array $options = [])
    {
        $this->manager = $manager;
        $this->options = $options;
    }

    /**
     * Adds a file to the manifest, ensuring no duplicate entries.
     *
     * If the file already exists in the manifest, it is replaced with the latest details.
     *
     * @param  string  $fileName  Partial path to the file.
     *
     * @throws TransferFileUriNotFoundException If the file cannot be found in either the temporary or download directory.
     */
    public function addToManifest(string $fileName): void
    {
        $tmpDir = $this->manager->getTmpPath();
        $tmpUri = $tmpDir.DS.$fileName;
        $downloadDir = $this->manager->getDownloadDir();
        $downloadUri = $downloadDir.DS.$fileName;

        if (file_exists($downloadUri)) {
            $uri = $downloadUri;
        } elseif (file_exists($tmpUri)) {
            $uri = $tmpUri;
        } else {
            throw new TransferFileUriNotFoundException("$fileName not found in \"$tmpDir\" or \"$downloadDir\"");
        }

        clearstatcache(true, $uri);
        $size = filesize($uri);

        // Assign file details to manifest using $fileName as the key
        $this->manifest[$fileName] = [
            'uri' => $uri,
            'name' => $fileName,
            'size' => $size,
        ];
    }

    /**
     * Determines if the file transfer process is complete.
     *
     * The transfer is considered complete if all files listed in the manifest exist
     * in the destination directory and match their expected sizes.
     *
     * @return bool True if all files are successfully transferred, otherwise false.
     */
    public function isCompleted(): bool
    {
        if (! $this->completed) {
            $destinationPath = $this->manager->getDestinationPath();

            // Verify each file's existence and size.
            foreach ($this->manifest as $fileName => $file) {
                $destinationUri = $destinationPath.DS.$fileName;

                // Remove any cached stat data for the destination file.
                clearstatcache(true, $destinationUri);

                // If file is missing or size is incorrect, transfer is incomplete.
                if (! file_exists($destinationUri) || filesize($destinationUri) !== $file['size']) {
                    return false;
                }
            }

            $this->completed = true;
        }

        return $this->completed;
    }

    /**
     * Removes all temporary files.
     */
    public function cleanup(): void
    {
        $tmpPath = $this->manager->getTmpPath();
        $this->rmContents($tmpPath);
    }
}
