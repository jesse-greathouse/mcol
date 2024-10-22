<?php

namespace App\Media\Transfer;

use App\Exceptions\TransferTarFileException;

use splitbrain\PHPArchive\Tar as TarArchive;

use \Exception,
    \FilesystemIterator,
    \RecursiveDirectoryIterator,
    \RecursiveIteratorIterator;


final class Tar extends Transfer implements TransferInterface
{
    /**
     * Transfers files with a .tar archive.
     *
     * @param string|null $uri
     * @return void
     */
    public function transfer(string $uri = null): void
    {
        $file = $this->manager->getFileUri();
        $tmpPath = $this->manager->getTmpPath();

        try {
            $tar = new TarArchive();
            $tar->open($file);
            $tar->extract($tmpPath);
        } catch (Exception $e) {
            throw new TransferTarFileException($e->getMessage());
        }

        try {
            $rdi = new RecursiveDirectoryIterator($tmpPath, FilesystemIterator::SKIP_DOTS);
            $rii = new RecursiveIteratorIterator($rdi);

            foreach ($rii as $di) {
                if ($di->isDir()) {
                    continue;
                }

                $uri = $di->getPath() . DIRECTORY_SEPARATOR . $di->getFilename();
                $this->addToManifest($uri);
                $copyFile = new CopyFile($this->manager);
                $copyFile->transfer($uri, $tmpPath);
            }
        } catch (Exception $e) {
            throw new TransferTarFileException($e->getMessage());
        }
    }
}
