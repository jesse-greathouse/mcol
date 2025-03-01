<?php

namespace App\Media\Transfer;

use App\Exceptions\TransferZipFileException;

use Exception,
    FilesystemIterator,
    ZipArchive,
    RecursiveDirectoryIterator,
    RecursiveIteratorIterator;

// Define DS constant for cross-platform compatibility if not already defined
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 * Class for handling file transfers in the form of a ZIP archive.
 *
 * Extends the abstract Transfer class and implements TransferInterface.
 * This class is responsible for extracting the contents of a ZIP file and transferring the extracted files.
 */
final class Zip extends Transfer implements TransferInterface
{
    /**
     * Transfers files from a ZIP archive to a temporary path.
     *
     * This method opens a ZIP file, extracts its contents to a temporary directory, and then processes
     * the extracted files by adding them to the transfer manifest and copying them.
     *
     * @param ?string|null $uri The URI to the ZIP archive to be transferred. Defaults to null.
     * @return void
     *
     * @throws TransferZipFileException If the ZIP file cannot be opened or if any errors occur during extraction.
     */
    public function transfer(?string $uri = null): void
    {
        $tmpPath = $this->manager->getTmpPath();

        // Extract ZIP archive
        $this->extractZipArchive($tmpPath);

        // Process extracted files
        $this->processExtractedFiles($tmpPath);
    }

    /**
     * Extracts the contents of a ZIP archive to a temporary directory.
     *
     * @param string $tmpPath The path where the contents will be extracted.
     *
     * @throws TransferZipFileException If there is an issue opening or extracting the ZIP file.
     */
    private function extractZipArchive(string $tmpPath): void
    {
        $file = $this->manager->getFileUri(); // Assign $file directly inside this method

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
    }

    /**
     * Processes the extracted files from the ZIP archive.
     *
     * Iterates through the extracted files, adds them to the transfer manifest, and then transfers each file.
     *
     * @param string $tmpPath The path where the files have been extracted.
     *
     * @throws TransferZipFileException If an error occurs while processing the extracted files.
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

                // Transfer each extracted file
                $copyFile = new CopyFile($this->manager);
                $copyFile->transfer($uri);
            }
        } catch (Exception $e) {
            throw new TransferZipFileException($e->getMessage());
        }
    }
}
