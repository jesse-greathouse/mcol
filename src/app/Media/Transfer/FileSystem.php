<?php

namespace App\Media\Transfer;

use App\Exceptions\InvalidDirectoryException;

use \FilesystemIterator,
    \DirectoryIterator,
    \RecursiveDirectoryIterator,
    \RecursiveIteratorIterator;

trait FileSystem
{
    // https://www.phpliveregex.com/p/Mzr
    const DOT_MASK = '/.*(\.\/|\.\\|\.\.).*/';

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
     * Returns true if the supplied uri has dots followed by a slash.
     * A Safety concern for backing out of designated directories into system files.
     *
     * @param string $uri
     * @return bool
     */
    public function hasDotSlash(string $uri): bool
    {
        $matches = [];
        return (preg_match(self::DOT_MASK, $uri, $matches)) ? true : false;
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
     * List Directory Contents
     *
     * @param string $uri
     * @return array<int, SplFileInfo>
     */
    public function list(string $uri): array
    {
        if (!is_dir($uri)) {
            throw new InvalidDirectoryException("Could not list contents of: \"$uri\", it is not a directory.");
        }

        $ls = [];

        foreach (new DirectoryIterator($uri) as $file) {
            if ($file->isDot()) continue;
            $ls[] = $file->getFileInfo();
        }

        return $ls;
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
