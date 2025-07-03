<?php

namespace App\Media;

use App\Exceptions\DirectoryAlreadyExistsException;
use App\Exceptions\DirectoryCreateFailedException;
use App\Exceptions\DirectoryNotWithinMediaStoreException;
use App\Exceptions\DirectoryRemoveMediaRootException;
use App\Exceptions\FileNotFoundException;
use App\Exceptions\MediaStoreDirectoryIndexOutOfBoundsException;
use App\Exceptions\SettingsIllegalStoreException;
use App\Exceptions\UriHasDotSlashException;
use App\FileSystem;
use App\Store\MediaStoreSettings;
use SplFileInfo;

/**
 * Store class for managing media store directories.
 * Provides methods to create, remove, and list directories within a media store.
 */
final class Store
{
    use FileSystem;

    /** @var MediaStoreSettings Settings for the Media Store. */
    private MediaStoreSettings $settings;

    /** @var array Contains a flattened list of all the stores as URIs. */
    private array $storeList = [];

    /** @var array<string, bool> Index of URIs tested to be branched from a media store. */
    private array $branchIndex = [];

    /**
     * Instantiates a Store object.
     * Store is a querying tool for displaying directory contents of media stores.
     *
     * @param  MediaStoreSettings  $settings  The settings for the media store.
     */
    public function __construct(MediaStoreSettings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Creates a directory.
     *
     * @param  string  $uri  The URI of the directory to create.
     * @return SplFileInfo The created directory.
     *
     * @throws UriHasDotSlashException If URI contains dot-slash patterns.
     * @throws DirectoryNotWithinMediaStoreException If URI is not within a media store.
     * @throws DirectoryAlreadyExistsException If directory already exists.
     * @throws DirectoryCreateFailedException If directory creation fails.
     */
    public function createDir(string $uri): SplFileInfo
    {
        // Rejects target URI with dot-slash patterns (./ or ../).
        if ($this->hasDotSlash($uri)) {
            throw new UriHasDotSlashException("Cannot create \"$uri\" because it has illegal form (./, ../).");
        }

        // Can only create directories inside a media store.
        if (! $this->isBranchOfMediaStore($uri)) {
            throw new DirectoryNotWithinMediaStoreException("Cannot create \"$uri\" because it is not branched from any media store.");
        }

        // Directory already exists.
        if (is_dir($uri)) {
            throw new DirectoryAlreadyExistsException("The directory: \"$uri\" already exists.");
        }

        // Prepare the path and create the directory.
        if (! $this->preparePath($uri)) {
            throw new DirectoryCreateFailedException("The directory: \"$uri\" could not be created.");
        }

        return new SplFileInfo($uri);
    }

    /**
     * Removes a file or directory.
     *
     * @param  string  $uri  The URI of the file or directory to remove.
     *
     * @throws UriHasDotSlashException If URI contains dot-slash patterns.
     * @throws DirectoryRemoveMediaRootException If URI is the root of a media store.
     * @throws DirectoryNotWithinMediaStoreException If URI is not within a media store.
     * @throws FileNotFoundException If file or directory does not exist.
     */
    public function rm(string $uri): void
    {
        // Rejects target URI with dot-slash patterns (./ or ../).
        if ($this->hasDotSlash($uri)) {
            throw new UriHasDotSlashException("Cannot remove \"$uri\" because it has illegal form (./, ../).");
        }

        // Prevent removing the root of a media store.
        $uri = $this->withoutTrailingSlash($uri);
        if (in_array($uri, $this->getStoreList())) {
            throw new DirectoryRemoveMediaRootException("Cannot remove \"$uri\" because it is the root of a media store.");
        }

        // Can only remove directories inside a media store.
        if (! $this->isBranchOfMediaStore($uri)) {
            throw new DirectoryNotWithinMediaStoreException("Cannot remove \"$uri\" because it is not branched from any media store.");
        }

        // File or directory does not exist.
        if (! file_exists($uri)) {
            throw new FileNotFoundException("The file or directory: \"$uri\" does not exist.");
        }

        // Recursively remove the file or directory.
        $this->recursiveRm($uri);
    }

    /**
     * Returns a directory listing of the root of a store by name.
     *
     * @param  string  $storeName  The name of the store.
     * @param  int  $index  The index of the store configuration.
     * @param  string|null  $sort  The sort order.
     * @param  string|null  $direction  The direction of sorting.
     * @return array<int, DirectoryIterator> The directory listing.
     *
     * @throws SettingsIllegalStoreException If the store is not found in the settings.
     * @throws MediaStoreDirectoryIndexOutOfBoundsException If the index is out of bounds.
     */
    public function getStoreRootDir(string $storeName, int $index = 0, ?string $sort = null, ?string $direction = null): array
    {
        $stores = $this->settings->toArray();

        if (! isset($stores[$storeName])) {
            throw new SettingsIllegalStoreException("System settings do not have the requested media store: \"$storeName\".");
        }

        if (! isset($stores[$storeName][$index])) {
            throw new MediaStoreDirectoryIndexOutOfBoundsException("System settings media store: \"$storeName\", does not have the requested index: \"$index\".");
        }

        return $this->getDir($stores[$storeName][$index], $sort, $direction);
    }

    /**
     * Returns the content of a directory.
     *
     * @param  string  $uri  The URI of the directory to list.
     * @param  string|null  $sort  The sort order.
     * @param  string|null  $direction  The direction of sorting.
     * @return array<int, DirectoryIterator> The directory contents.
     *
     * @throws UriHasDotSlashException If URI contains dot-slash patterns.
     * @throws DirectoryNotWithinMediaStoreException If URI is not within a media store.
     */
    public function getDir(string $uri, ?string $sort = null, ?string $direction = null): array
    {
        // Rejects target URI with dot-slash patterns (./ or ../).
        if ($this->hasDotSlash($uri)) {
            throw new UriHasDotSlashException("Cannot list contents of \"$uri\" because it has illegal form (./, ../).");
        }

        // Can only list directories within a media store.
        if (! $this->isBranchOfMediaStore($uri)) {
            throw new DirectoryNotWithinMediaStoreException("Cannot list contents of \"$uri\" because it is not branched from any media store.");
        }

        return $this->list($uri, $sort, $direction);
    }

    /**
     * Checks if the supplied URI is branched from any of the media stores.
     *
     * @param  string  $uri  The URI to check.
     * @return bool True if the URI is branched from a media store, false otherwise.
     */
    public function isBranchOfMediaStore(string $uri): bool
    {
        // Check cache (branch index).
        if (isset($this->branchIndex[$uri])) {
            return $this->branchIndex[$uri];
        }

        // Check if the URI is part of any store.
        foreach ($this->getStoreList() as $store) {
            if (strpos($uri, $store) !== false) {
                $this->branchIndex[$uri] = true;

                return true;
            }
        }

        // Cache the result as false.
        $this->branchIndex[$uri] = false;

        return false;
    }

    /**
     * Returns a flattened list of media stores from the settings.
     *
     * @return array The list of stores.
     */
    public function getStoreList(): array
    {
        // Cache store list if not already populated.
        if (empty($this->storeList)) {
            $this->storeList = array_merge(...array_values($this->settings->toArray()));
        }

        return $this->storeList;
    }
}
