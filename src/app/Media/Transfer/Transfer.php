<?php

namespace App\Media\Transfer;

use App\Media\TransferManager,
    App\Media\Transfer\FileSystem;

abstract class Transfer
{
    use FileSystem;

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
        clearstatcache(true, $uri);
        $size = filesize($uri);
        $fileName = basename($uri);

        $tmpPath = $this->manager->getTmpPath();
        if (null !== $tmpPath) {
            $fileName = (str_replace($tmpPath, '', $uri));

            // Remove the slash if it starts with one.
            if (0 === strpos($fileName, DIRECTORY_SEPARATOR)) {
                $fileName = ltrim($fileName, $fileName[0]);
            }
        }

        $this->manifest[] = [
            'name' => $fileName,
            'size' => $size,
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
        $this->rmContents($tmpPath);
    }
}
