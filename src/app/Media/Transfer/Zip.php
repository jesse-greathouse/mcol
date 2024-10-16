<?php

namespace App\Media\Transfer;

use App\Exceptions\TransferZipFileException;

use \Exception,
    \FilesystemIterator,
    \ZipArchive,
    \RecursiveDirectoryIterator,
    \RecursiveIteratorIterator;


final class Zip extends Transfer implements TransferInterface
{
    /**
     * Transfers files with a .zip archive.
     *
     * @param string|null $uri
     * @return void
     */
    public function transfer(string $uri = null): void
    {
        $file = $this->manager->getFileUri();
        $tmpPath = $this->manager->getTmpPath();

        try {
            $zip = new ZipArchive();
            if (true === $zip->open($file)) {
                $zip->extractTo($tmpPath);
                $zip->close();
            } else {
                throw new TransferZipFileException("Failed to open Zip Archive: \"$file\".");
            }
        } catch (Exception $e) {
            throw new TransferZipFileException($e->getMessage());
        }

        try {
            $rdi = new RecursiveDirectoryIterator($tmpPath, FilesystemIterator::SKIP_DOTS);
            $rii = new RecursiveIteratorIterator($rdi);

            foreach ($rii as $di) {
                $baseName = $di->getFilename();
                $uri = $tmpPath . DIRECTORY_SEPARATOR . $baseName;
                $this->addToManifest($uri);
                $copyFile = new CopyFile($this->manager);
                $copyFile->transfer($uri);
            }
        } catch (Exception $e) {
            throw new TransferZipFileException($e->getMessage());
        }
    }
}
