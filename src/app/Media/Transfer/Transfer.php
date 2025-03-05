<?php

namespace App\Media\Transfer;

use App\FileSystem,
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
     * @param string $uri
     * @return void
     */
    public function addToManifest(string $uri): void
    {
        clearstatcache(true, $uri);
        $size = filesize($uri);
        $fileName = str_replace($this->manager->getFileDirName(), '', $uri);

        $tmpPath = $this->manager->getTmpPath();
        if (null !== $tmpPath) {
            $fileName = str_replace($tmpPath, '', $uri);

            // Remove the leading slash if it starts with one.
            if (strpos($fileName, DIRECTORY_SEPARATOR) === 0) {
                $fileName = ltrim($fileName, DIRECTORY_SEPARATOR);
            }
        }

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
        if ($this->completed) {
            return true;
        }

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
