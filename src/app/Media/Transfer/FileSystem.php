<?php

namespace App\Media\Transfer;

use App\Exceptions\DirectoryDirectionSortIllegalOptionException,
    App\Exceptions\DirectorySortIllegalOptionException,
    App\Exceptions\InvalidDirectoryException;

use \FilesystemIterator,
    \DirectoryIterator,
    \RecursiveDirectoryIterator,
    \RecursiveIteratorIterator;

trait FileSystem
{
    // https://www.phpliveregex.com/p/Mzr
    const DOT_MASK = '/.*(\.\/|\.\\|\.\.).*/';

    const SORT_FILENAME = 'filename';
    const SORT_MODIFIED = 'modified';
    const SORT_DEFAULT = self::SORT_FILENAME;

    const DIRECTION_SORT_ASC = 'asc';
    const DIRECTION_SORT_DESC = 'desc';
    const DIRECTION_SORT_DEFAULT = self::DIRECTION_SORT_ASC;

    const SORT_OPTIONS = [
        self::SORT_FILENAME,
        self::SORT_MODIFIED,
    ];

    const DIRECTION_SORT_OPTIONS = [
        self::DIRECTION_SORT_ASC,
        self::DIRECTION_SORT_DESC,
    ];

    const SORT_METHOD_MAP = [
        self::SORT_FILENAME => 'getFilename',
        self::SORT_MODIFIED => 'getMTime',
    ];

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
     * List Directory Contents
     *
     * @param string $uri
     * @param string $sort
     * @param string $direction
     * @return array<int, SplFileInfo>
     */
    public function list(string $uri, $sort = null, $direction = null): array
    {
        if (null === $sort) {
            $sort = self::SORT_DEFAULT;
        } else {
            if (!in_array($sort, self::SORT_OPTIONS)) {
                $options = implode(', ', self::SORT_OPTIONS);
                throw new DirectorySortIllegalOptionException("Sorting by: \"$sort\" is not an option. (Available options are: \"$options\".)");
            }
        }

        if (null === $direction) {
            $direction = self::DIRECTION_SORT_DEFAULT;
        } else {
            if (!in_array($direction, self::DIRECTION_SORT_OPTIONS)) {
                $options = implode(', ', self::DIRECTION_SORT_OPTIONS);
                throw new DirectoryDirectionSortIllegalOptionException("Sorting direction: \"$direction\" is not an option. (Available options are: \"$options\".)");
            }
        }

        if (!is_dir($uri)) {
            throw new InvalidDirectoryException("Could not list contents of: \"$uri\", it is not a directory.");
        }

        $sortMethod = self::SORT_METHOD_MAP[$sort];
        $ls = [];

        foreach (new DirectoryIterator($uri) as $file) {
            if ($file->isDot()) continue;
            $ls[$file->$sortMethod()] = $file->getFileInfo();
        }

        $sorted = $this->sort($ls, $sort, $direction);

        return $sorted;
    }

    /**
     * Sorts an array of objects product by the list() method.
     *
     * @param array<string, SplFileInfo>
     * @param string $sort
     * @return array<int, SplFileInfo>
     */
    private function sort(array $ls, string $sort, string $direction): array
    {
        if ($direction === self::DIRECTION_SORT_DESC) {
            // Sort array by keys in reverse.
            krsort($ls, SORT_FLAG_CASE | SORT_NATURAL);
        } else {
            // Sort array by keys.
            ksort($ls, SORT_FLAG_CASE | SORT_NATURAL);
        }

        // Separate the Directories from the files to put the directories on top.
        $directories = [];
        $files = [];
        foreach($ls as $key => $val) {
            if ($val->isDir()) {
                $directories[] = $val;
            } else {
                $files[] = $val;
            }
        }

        return array_merge($directories, $files);
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

    /**
     * Removes the trailing slash from a uri if it has one
     *
     * @param string $uri
     * @return string
     */
    public function withoutTrailingSlash(string $uri): string
    {
        if (substr($uri, -1) === DIRECTORY_SEPARATOR) {
            $uri = substr_replace($uri, '', -1);
        }

        return $uri;
    }

}
