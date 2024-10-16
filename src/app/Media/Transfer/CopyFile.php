<?php

namespace App\Media\Transfer;

use App\Exceptions\TransferFileCopyException;

final class CopyFile extends Transfer implements TransferInterface
{
    /**
     * Transfers a file by copy
     *
     * @param string|null $uri
     * @return void
     */
    public function transfer(string $uri = null): void
    {
        $uri = (null !== $uri) ? $uri : $this->manager->getFileUri();
        $baseName = basename($uri);
        $newPath = $this->manager->getDestinationPath();
        $newFile = $newPath . DIRECTORY_SEPARATOR . $baseName;
        $this->addToManifest($uri);
        if (!copy($uri, $newFile)) {
            throw new TransferFileCopyException("failed to copy \"$uri\" to \"$newFile\".");
        }
    }

    public function cleanup(): void
    {
        return;
    }
}
