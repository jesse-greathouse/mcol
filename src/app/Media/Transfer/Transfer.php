<?php

namespace App\Media\Transfer;

use App\Exceptions\TransferFileUriNotFoundException,
    App\FileSystem,
    App\Media\TransferManager;

// Define DS constant for cross-platform compatibility if not already defined
if (!defined('DS')) {
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
     *
     * @var TransferManager
     */
    protected TransferManager $manager;

    /**
     * Array of options.
     *
     * @var array
     */
    protected array $options = [];

    /**
     * List of files to be transferred with their original file size.
     *
     * @var array
     */
    protected array $manifest = [];

    /**
     * Tracks whether the transfer is completed.
     *
     * @var bool
     */
    protected bool $completed = false;

    /**
     * Transfer constructor.
     *
     * @param TransferManager $manager
     * @param array $options
     */
    public function __construct(TransferManager $manager, array $options = [])
    {
        $this->manager = $manager;
        $this->options = $options;
    }

    /**
     * Adds another file to the manifest.
     *
     * @param string $fileName Partial path to the file.
     * @return void
     */
    public function addToManifest(string $fileName): void
    {
        $tmpDir = $this->manager->getTmpPath();
        $tmpUri = $tmpDir . DS . $fileName;
        $downloadDir = $this->manager->getDownloadDir();
        $downloadUri = $downloadDir . DS . $fileName;

        if (file_exists($downloadUri)) {
            $uri = $downloadUri;
        } elseif (file_exists($tmpUri)) {
            $uri = $tmpUri;
        } else {
            throw new TransferFileUriNotFoundException("$fileName not found in \"$tmpDir\" or \"$downloadDir\"");
        }

        clearstatcache(true, $uri);
        $size = filesize($uri);

        $this->manifest[] = [
            'uri'   => $uri,
            'name'  => $fileName,
            'size'  => $size,
        ];
    }

    /**
     * Checks if the transfer is completed by verifying file existence and size.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        if (!$this->completed) {
            // Test each file in the manifest.
            foreach ($this->manifest as $file) {
                $fileName = $file['name'];
                $destinationUri = $this->manager->getDestinationPath() . DS . $fileName;

                // Remove any statcache that $destinationUri may have.
                clearstatcache(true, $destinationUri);

                // If $destinationUri doesn't exist, or size is wrong, then not completed.
                if (!file_exists($destinationUri) || (filesize($destinationUri) !== $file['size'])) {
                    return false;
                }
            }

            $this->completed = true;
        }

        return $this->completed;
    }

    /**
     * Removes all temporary files.
     *
     * @return void
     */
    public function cleanup(): void
    {
        $tmpPath = $this->manager->getTmpPath();
        $this->rmContents($tmpPath);
    }
}
