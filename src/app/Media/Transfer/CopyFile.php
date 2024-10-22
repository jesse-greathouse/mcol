<?php

namespace App\Media\Transfer;

use App\Exceptions\TransferFileCopyException,
    App\Media\Transfer\FileSystem;

final class CopyFile extends Transfer implements TransferInterface
{
    use Filesystem;

    /**
     * Transfers a file by copy
     *
     * @param string|null $uri
     * @param string|null $tmpPath
     * @return void
     */
    public function transfer(string $uri = null, $tmpPath = null): void
    {
        $uri = (null !== $uri) ? $uri : $this->manager->getFileUri();
        $fileName = basename($uri);

        if (null !== $tmpPath) {
            $fileName = (str_replace($tmpPath, '', $uri));

            // Remove the slash if it starts with one.
            if (0 === strpos($fileName, DIRECTORY_SEPARATOR)) {
                $fileName = ltrim($fileName, $fileName[0]);
            }
        }

        $newFile = $this->prepareNewFile($fileName);
        $this->addToManifest($uri);
        if (!copy($uri, $newFile)) {
            throw new TransferFileCopyException("failed to copy \"$uri\" to \"$newFile\".");
        }
    }

    /**
     * Handles new file path and making sure the directory structure exists.
     *
     * @param string $fileName
     * @return string
     */
    private function prepareNewFile(string $fileName): string
    {
        $newPath = $this->manager->getDestinationPath();
        $newFile = $newPath . DIRECTORY_SEPARATOR . $fileName;
        ['dirname' => $newDir] = pathInfo($newFile);
        $this->preparePath($newDir);
        return $newFile;
    }

    /**
     *
     */
    public function cleanup(): void
    {
        return;
    }
}
