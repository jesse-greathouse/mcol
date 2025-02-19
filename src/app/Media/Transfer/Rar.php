<?php

namespace App\Media\Transfer;

use App\Exceptions\TransferRarFileException;

use Exception,
    FilesystemIterator,
    RarArchive,
    RecursiveDirectoryIterator,
    RecursiveIteratorIterator;

// Define DS constant for cross-platform compatibility if not already defined
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 * Class handling the transfer of .rar files.
 *
 * This class implements the `TransferInterface` and provides the logic for transferring
 * files from a RAR archive, extracting them to a temporary directory, and managing
 * manifest data.
 */
final class Rar extends Transfer implements TransferInterface
{
    /**
     * Transfers files with a .rar archive.
     *
     * This method opens the .rar file, extracts its contents to a temporary directory,
     * and adds each file to the manifest. It also initiates a file transfer for each extracted file.
     *
     * @param string|null $uri The URI of the RAR file to be transferred.
     * @return void
     * @throws TransferRarFileException If there is an issue with opening the RAR file or extracting its contents.
     */
    public function transfer(string $uri = null): void
    {
        $tmpPath = $this->manager->getTmpPath();

        $this->extractRarFile($tmpPath);
        $this->processExtractedFiles($tmpPath);
    }

    /**
     * Extracts the contents of a RAR archive to the specified directory.
     *
     * @param string $tmpPath The temporary path to extract files to.
     * @return void
     * @throws TransferRarFileException If there is an issue opening the RAR archive.
     */
    private function extractRarFile(string $tmpPath): void
    {
        $file = $this->manager->getFileUri();

        try {
            $rar = RarArchive::open($file);
            if ($rar === false) {
                throw new TransferRarFileException("Failed to open RAR Archive: \"$file\".");
            }

            $entries = $rar->getEntries();
            foreach ($entries as $entry) {
                $entry->extract($tmpPath);
            }
        } catch (Exception $e) {
            throw new TransferRarFileException($e->getMessage());
        }
    }

    /**
     * Processes each extracted file in the temporary directory.
     *
     * This method adds the extracted files to the manifest and transfers them using
     * the `CopyFile` class.
     *
     * @param string $tmpPath The temporary path where the files are extracted.
     * @return void
     * @throws TransferRarFileException If there is an issue with file iteration or copying.
     */
    private function processExtractedFiles(string $tmpPath): void
    {
        try {
            $rdi = new RecursiveDirectoryIterator($tmpPath, FilesystemIterator::SKIP_DOTS);
            $rii = new RecursiveIteratorIterator($rdi);

            foreach ($rii as $di) {
                $baseName = $di->getFilename();
                $uri = $tmpPath . DS . $baseName;
                $this->addToManifest($uri);

                $copyFile = new CopyFile($this->manager);
                $copyFile->transfer($uri);
            }
        } catch (Exception $e) {
            throw new TransferRarFileException($e->getMessage());
        }
    }
}
