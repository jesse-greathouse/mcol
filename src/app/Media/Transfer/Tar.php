<?php

namespace App\Media\Transfer;

use App\Exceptions\TransferTarFileException;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use splitbrain\PHPArchive\Tar as TarArchive;

// Define DS constant for cross-platform compatibility if not already defined
if (! defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 * Handles file transfers with .tar archives.
 *
 * This class extends the abstract Transfer class and implements the TransferInterface.
 * It is responsible for extracting a .tar file and copying the files to the destination.
 */
final class Tar extends Transfer implements TransferInterface
{
    /**
     * Transfers files with a .tar archive.
     *
     * @param  ?string|null  $uri  The URI of the source file to transfer. Defaults to null.
     *
     * @throws TransferTarFileException If there is an error extracting or copying the .tar archive files.
     */
    public function transfer(?string $uri = null): void
    {
        // Get the temporary path from the TransferManager
        $tmpPath = $this->manager->getTmpPath();

        // tEST the current uri to be something useable
        if (! $tmpPath = $this->manager->getTmpPath()) {
            return;
        }

        // Extract the .tar file
        $this->extractTar($tmpPath);

        // Copy the extracted files
        $this->copyExtractedFiles($tmpPath);
    }

    /**
     * Extracts the .tar archive to the specified temporary path.
     *
     * @param  string  $tmpPath  The temporary path where files will be extracted.
     *
     * @throws TransferTarFileException If there is an error extracting the .tar archive.
     */
    private function extractTar(string $tmpPath): void
    {
        $file = $this->manager->getFileUri();

        try {
            // Open the .tar archive and extract its contents
            $tar = new TarArchive;
            $tar->open($file);
            $tar->extract($tmpPath);
        } catch (Exception $e) {
            throw new TransferTarFileException('Error extracting TAR file: '.$e->getMessage());
        }
    }

    /**
     * Copies the extracted files to the destination.
     *
     * @param  string  $tmpPath  The temporary path where files are extracted.
     *
     * @throws TransferTarFileException If there is an error copying the extracted files.
     */
    private function copyExtractedFiles(string $tmpPath): void
    {
        try {
            // Initialize RecursiveDirectoryIterator to iterate over extracted files
            $rdi = new RecursiveDirectoryIterator($tmpPath, FilesystemIterator::SKIP_DOTS);
            $rii = new RecursiveIteratorIterator($rdi);

            // Iterate over all files in the extracted .tar
            foreach ($rii as $di) {
                if ($di->isDir()) {
                    continue;
                }

                $uri = $di->getPath().DS.$di->getFilename();

                // Remove the tmp path from the path to leave just the uri of the file.
                $fileName = str_replace($tmpPath, '', $uri);

                // Remove leading slash if present
                if (str_starts_with($fileName, DS)) {
                    $fileName = ltrim($fileName, $fileName[0]);
                }

                $this->addToManifest($fileName);

                // Copy the extracted file to the destination
                $copyFile = new CopyFile($this->manager);
                $copyFile->transfer($uri, $tmpPath);
            }
        } catch (Exception $e) {
            throw new TransferTarFileException('Error copying extracted files: '.$e->getMessage());
        }
    }
}
