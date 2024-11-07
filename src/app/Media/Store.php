<?php

namespace App\Media;

use App\Exceptions\DirectoryAlreadyExistsException,
    App\Exceptions\DirectoryCreateFailedException,
    App\Exceptions\DirectoryNotWithinMediaStoreException,
    App\Exceptions\DirectoryRemoveMediaRootException,
    App\Exceptions\FileNotFoundException,
    App\Exceptions\MediaStoreDirectoryIndexOutOfBoundsException,
    App\Exceptions\SettingsIllegalStoreException,
    App\Exceptions\UriHasDotSlashException,
    App\FileSystem,
    App\Store\MediaStoreSettings;

use \SplFileInfo;

final class Store
{
    use FileSystem;

    /**
     * Settings for the Media Store.
     *
     * @var MediaStoreSettings
     */
    private $settings;

    /**
     * Contains a flattened list of all the stores as uri's
     *
     * @var array
     */
    private array $storeList = [];

    /**
     * Tracks an Index of uris that are tested to be branched from a media store.
     * This index ensures that an expensive lookup is never made twice.
     * The bool result of isBranchOfMediaStore is indexed by uri.
     *
     * @var array<string, bool>
     */
    private array $branchIndex = [];

    /**
     * Instansiates a Store object.
     * Store is a querying tool for displaying directoriy contents of media stores.
     */
    public function __construct(MediaStoreSettings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Creates a directory.
     *
     * @param string $uri
     * @return SplFileInfo
     */
    public function createDir(string $uri): SplFileInfo
    {
        // This condition will reject the target uri if it has dot slash patterns.
        if ($this->hasDotSlash($uri)) {
            throw new UriHasDotSlashException(
                "Cannot create \"$uri\" because it has illegal form (./, ../)"
            );
        }

        // Can only create a directory inside a store.
        if (!$this->isBranchOfMediaStore($uri)) {
            throw new DirectoryNotWithinMediaStoreException(
                "Cannot create \"$uri\" because it is not branched from any media store."
            );
        }

        // Directory already exists.
        if (is_dir($uri)) {
            throw new DirectoryAlreadyExistsException("The directory: \"$uri\" already exists.");
        }

        if (!$this->preparePath($uri)) {
            throw new DirectoryCreateFailedException("The directory: \"$uri\" already exists.");
        }

        return new SplFileInfo($uri);
    }

    /**
     * Removes a File or Directory.
     *
     * @param string $uri
     * @return void
     */
    public function rm(string $uri): void
    {
        // This condition will reject the target uri if it has dot slash patterns.
        if ($this->hasDotSlash($uri)) {
            throw new UriHasDotSlashException(
                "Cannot remove \"$uri\" because it has illegal form (./, ../)"
            );
        }

        // Cannot remove the root of a Media Store.
        $uri = $this->withoutTrailingSlash($uri);
        if (in_array($uri, $this->getStoreList())) {
            throw new DirectoryRemoveMediaRootException(
                "Cannot remove \"$uri\" because it is the root of a Media Store."
            );
        }

        // Can only remove a directory inside a store.
        if (!$this->isBranchOfMediaStore($uri)) {
            throw new DirectoryNotWithinMediaStoreException(
                "Cannot remove \"$uri\" because it is not branched from any media store."
            );
        }

        // File or Directory does not exist.
        if (!file_exists($uri)) {
            throw new FileNotFoundException("The file or directory: \"$uri\" does not exist.");
        }

        $this->recursiveRm($uri);
    }

    /**
     * Returns a directory listing of the root of a store by name.
     *
     * @param string $storeName
     * @param int $index
     * @param string $sort
     * @param string $direction
     * @return array<int, DirectoryIterator>
     */
    public function  getStoreRootDir(string $storeName, int $index = 0, $sort = null, $direction = null): array
    {
        $stores = $this->settings->toArray();

        if (!isset($stores[$storeName])) {
            throw new SettingsIllegalStoreException(
                "System settings does not have the requested media store: \"$storeName\"."
            );
        }

        if (!isset($stores[$storeName][$index])) {
            throw new MediaStoreDirectoryIndexOutOfBoundsException(
                "System settings media store: \"$storeName\", does not have the requested index: \"$index\"."
            );
        }

        return $this->getDir($stores[$storeName][$index], $sort, $direction);
    }

    /**
     * Returns the content of a directory.
     *
     * @param string $uri
     * @param string $sort
     * @param string $direction
     * @return array<int, DirectoryIterator>
     */
    public function getDir(string $uri, $sort = null, $direction = null): array
    {

        // It's a security concern to show directories that contain a dot and slash like: ../../
        // This condition will reject any attempt to feed the system uris formed like this.
        if ($this->hasDotSlash($uri)) {
            throw new UriHasDotSlashException(
                "Cannot list contents of \"$uri\" because it has illegal form (./, ../)"
            );
        }

        // To List a directory it should only be branched from the root of the store.
        // Anything outside a store base directory is out of bounds and will be rejected.
        if (!$this->isBranchOfMediaStore($uri)) {
            throw new DirectoryNotWithinMediaStoreException(
                "Cannot list contents of \"$uri\" because it is not branched from any media store."
            );
        }

        return $this->list($uri, $sort, $direction);
    }

    /**
     * True or False if the supplied uri is branched from any of the meda stores.
     *
     * @param string $uri
     * @return bool
     */
    public function isBranchOfMediaStore(string $uri): bool
    {
        if (!isset($this->branchIndex[$uri])) {
            $this->branchIndex[$uri] = false;

            foreach($this->getStoreList() as $store) {
                if (false !== strpos($uri, $store)) {
                    $this->branchIndex[$uri] = true;
                    break;
                }
            }

            return $this->branchIndex[$uri];
        } else {
            return $this->branchIndex[$uri];
        }
    }

    /**
     * Returns a flattened list of media stores from the settings.
     *
     * @return array
     */
    public function getStoreList(): array
    {
        if (0 >= count($this->storeList)) {
            $this->storeList = array_merge(...array_values($this->settings->toArray()));
        }

        return $this->storeList;
    }
}
