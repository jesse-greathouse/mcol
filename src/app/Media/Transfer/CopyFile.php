<?php

namespace App\Media\Transfer;

use App\Exceptions\TransferFileCopyException,
    App\FileSystem;

/**
 * Class responsible for transferring files by copying.
 */
final class CopyFile extends Transfer implements TransferInterface
{
    use Filesystem;

    /** @var string|null File URI to be transferred */
    private ?string $uri;

    /** @var string|null Temporary path for file processing */
    private ?string $tmpPath;

    /**
     * Transfers a file by copying it to a new location.
     *
     * @param string|null $uri URI of the file to be transferred
     * @param string|null $tmpPath Temporary path to adjust the file name if necessary
     * @return void
     * @throws TransferFileCopyException If the copy operation fails
     */
    public function transfer(?string $uri = null, ?string $tmpPath = null): void
    {
        $this->uri = $uri ?? $this->manager->getFileUri();
        $fileName = basename($this->uri);

        if ($tmpPath !== null) {
            $fileName = str_replace($tmpPath, '', $this->uri);

            // Remove leading slash if present
            if (str_starts_with($fileName, DIRECTORY_SEPARATOR)) {
                $fileName = ltrim($fileName, $fileName[0]);
            }
        }

        $newFile = $this->prepareNewFile($fileName);
        $this->addToManifest($this->uri);

        if (!copy($this->uri, $newFile)) {
            throw new TransferFileCopyException("Failed to copy \"$this->uri\" to \"$newFile\".");
        }
    }

    /**
     * Prepares the new file path and ensures the directory exists.
     *
     * @param string $fileName The file name to be copied
     * @return string The full path to the new file
     */
    private function prepareNewFile(string $fileName): string
    {
        $newPath = $this->manager->getDestinationPath();
        $newFile = $newPath . DIRECTORY_SEPARATOR . $fileName;
        $newDir = pathinfo($newFile, PATHINFO_DIRNAME);
        $this->preparePath($newDir);

        return $newFile;
    }

    /**
     * Cleanup method, currently not implemented.
     *
     * @return void
     */
    public function cleanup(): void
    {
        // No cleanup is needed as of now
    }
}
