<?php

namespace App\Media\Transfer;

use App\Media\TransferManager;

abstract class Transfer
{
    /**
     * Holds the TransferManager instance.
     *
     * @var TransferManager
     */
    protected $manager;

    /**
     * Array of options
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
     * @var boolean
     */
    protected bool $completed = false;

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
    public function addToManifest(string $uri)
    {
        $this->manifest[] = [
            'name' => basename($uri),
            'size' => filesize($uri),
        ];
    }

    /**
     * If manifiest is not verified, runs through all the files in the manifest to see if they're finished.
     *
     * @return boolean
     */
    public function isCompleted(): bool
    {
        if (!$this->completed) {
            $this->completed = true;

            foreach ($this->manifest as $file) {
                $uri = $this->manager->getDestinationPath() . DIRECTORY_SEPARATOR . $file['name'];

                if (!file_exists($uri)) {
                    $this->completed = false;
                    break;
                }

                clearstatcache(true, $uri); // clears the caching of filesize
                $destinationSize = fileSize($uri);

                if ($file['size'] !== $destinationSize) {
                    $this->completed = false;
                    break;
                }
            }
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
        $tmpPath = $tmpPath = $this->manager->getTmpPath();
        foreach ($this->manifest as $file) {
            $uri = $tmpPath . DIRECTORY_SEPARATOR . $file['name'];
            if (file_exists($uri)) {
                $this->recursiveRm($uri);
            }
        }
        rmdir($tmpPath);
    }

    /**
     * Deletes the file, or recursively removes the directly.
     *
     * @param string $uri
     * @return void
     */
    public function recursiveRm(string $uri): void
    {
        if (!is_dir($uri)) {
            unlink($uri);
            return;
        }

        $rdi = new RecursiveDirectoryIterator($uri, FilesystemIterator::SKIP_DOTS);
        $rii = new RecursiveIteratorIterator($rdi);

        foreach ($rii as $di) {
            $full = $di->getPathname();
            if (is_dir($full) ) {
                $this->recursiveRm($full);
            } else {
                unlink($full);
            }
        }

        rmdir($uri);
    }
}
