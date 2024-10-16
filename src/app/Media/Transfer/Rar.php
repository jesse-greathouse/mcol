<?php

namespace App\Media\Transfer;

use App\Exceptions\TransferRarFileException;

use \Exception,
    \FilesystemIterator,
    \RarArchive,
    \RecursiveDirectoryIterator,
    \RecursiveIteratorIterator;


final class Rar extends Transfer implements TransferInterface
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
            $rar = RarArchive::open($file);
            if (true === $rar) {
                $entries = $rar->getEntries();
                foreach ($entries as $entry) {
                    $entry->extract($tmpPath);
                }
            } else {
                throw new TransferRarFileException("Failed to open Rar Archive: \"$file\".");
            }
        } catch (Exception $e) {
            throw new TransferRarFileException($e->getMessage());
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
            throw new TransferRarFileException($e->getMessage());
        }
    }
}
