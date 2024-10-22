<?php

namespace App\Media\Transfer;

use \FilesystemIterator,
    \RecursiveDirectoryIterator,
    \RecursiveIteratorIterator;

trait FileSystem
{
    /**
     * Make the path work recursively
     *
     * @param  string  $path  location of where the data files will be stored.
     * @return bool
     */
    public function preparePath(string $path): bool
    {
        if (is_dir($path)) return true;

        $prev_path = substr($path, 0, strrpos($path, DIRECTORY_SEPARATOR, -2) + 1 );
        $return = $this->preparePath($prev_path);
        return ($return && is_writable($prev_path)) ? mkdir($path) : false;
    }

    /**
     * Removes the contents of the directory, without removing the directory.
     *
     * @param string $uri
     * @return void
     */
    public function rmContents(string $uri): void
    {
        $rdi = new RecursiveDirectoryIterator($uri, FilesystemIterator::SKIP_DOTS);
        $rii = new RecursiveIteratorIterator($rdi);

        foreach ($rii as $di) {
            $full = $di->getPathname();
            if ($di->isDir() ) {
                $this->recursiveRm($full);
            } else {
                unlink($full);
            }
        }
    }

    /**
     * Deletes the file, or recursively removes the directly.
     *
     * @param string $uri
     * @return void
     */
    public function recursiveRm(string $uri): void
    {
        if (!is_dir($uri)) {
            unlink($uri);
            return;
        }

        $rdi = new RecursiveDirectoryIterator($uri, FilesystemIterator::SKIP_DOTS);
        $rii = new RecursiveIteratorIterator($rdi);

        foreach ($rii as $di) {
            $full = $di->getPathname();
            if (is_dir($full) ) {
                $this->recursiveRm($full);
            } else {
                unlink($full);
            }
        }

        rmdir($uri);
    }

}
