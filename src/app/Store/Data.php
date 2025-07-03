<?php

namespace App\Store;

use App\Exceptions\DataStoreInvalidPropertyException;
use App\Exceptions\DataStoreInvalidStorePathException;
use App\Exceptions\DataStoreUnableToInitializeException;
use App\Exceptions\DataStoreUnsupportedFormatException;
use Symfony\Component\Yaml\Yaml;

abstract class Data
{
    const YAML_FILE_EXTENSION = 'yml';

    /**
     * location of where the data files will be stored.
     */
    protected string $path;

    /**
     * The body of data values that can be stored and retrieved.
     */
    protected array $storable = [];

    /**
     * The configuration of the Yaml Parser.
     */
    protected array $config = [
        'flags' => 0,
        'inline' => 2,
        'indent' => 4,
    ];

    /**
     * @param  ?array  $config
     */
    public function __construct(string $path, array $config = [])
    {
        $this->setPath($path);
        $this->init();
        $this->configure($config);
        $cached = Yaml::parseFile($this->getPath(), $this->config['flags']);

        $this->storable = is_array($cached)
            ? array_merge($this->storable, $cached)
            : $this->storable;
    }

    /**
     * Initializes the datastore in the file system.
     */
    public function init(): void
    {
        $uri = $this->getPath();
        if (file_exists($uri)) {
            return;
        }

        [
            'dirname' => $dirName,
            'basename' => $baseName,
            'extension' => $extension,
            'filename' => $fileName
        ] = pathinfo($uri);
        if ($extension !== self::YAML_FILE_EXTENSION) {
            throw new DataStoreUnsupportedFormatException(
                "Data Store: \"$baseName\" has unsupported format. Try with: \"$fileName.".self::YAML_FILE_EXTENSION.'".');
        }

        if (! $this->createPath($dirName)) {
            throw new DataStoreInvalidStorePathException("Could not create: \"$dirName\" for data store.");
        }

        if (! touch($uri)) {
            throw new DataStoreUnableToInitializeException("Could not initialize the data store: \"$uri\".");
        }
    }

    /**
     * Returns the storable object as an array.
     */
    public function toArray(): array
    {
        return $this->getStorable();
    }

    /**
     * Returns a list of all the storable keys.
     */
    public function getKeys(): array
    {
        return array_keys($this->storable);
    }

    /**
     * Returns the storable array.
     */
    public function getStorable(): array
    {
        return $this->storable;
    }

    /**
     * Save the storable array into the yaml configuration file.
     */
    public function save(): void
    {
        $yamlStr = Yaml::dump($this->storable, $this->config['inline'], $this->config['indent'], $this->config['flags']);
        file_put_contents($this->path, $yamlStr);
    }

    /**
     * Override the default configuration with any valid keys.
     *
     * @return void
     */
    protected function configure(array $config)
    {
        if (count($config) > 0) {
            foreach ($this->config as $key => $val) {
                if (isset($config[$key])) {
                    $this->config[$key] = $config[$key];
                }
            }
        }
    }

    /**
     * Magic function to retrieve properties dynamically.
     */
    public function __get(string $name): mixed
    {
        if (array_key_exists($name, $this->storable)) {
            return $this->storable[$name];
        } else {
            $class = static::class;
            throw new DataStoreInvalidPropertyException("Accessed invalid property: $name on $class");
        }
    }

    /**
     * Magic function to set properties dynamically.
     */
    public function __set(string $name, mixed $value): void
    {
        $this->storable[$name] = $value;
    }

    /**
     * Magic function to dynamically check if a property is set.
     */
    public function __isset(string $name): bool
    {
        return isset($this->storable[$name]);
    }

    /**
     * Magic function to unset properties dynamically.
     */
    public function __unset(string $name): void
    {
        unset($this->storable[$name]);
    }

    /**
     * Get location of where the data files will be stored.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set location of where the data files will be stored.
     *
     * @param  string  $path  location of where the data files will be stored.
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Make the file path work recursively
     *
     * @param  string  $path  location of where the data files will be stored.
     */
    protected function createPath(string $path): bool
    {
        if (is_dir($path)) {
            return true;
        }

        $prev_path = substr($path, 0, strrpos($path, DIRECTORY_SEPARATOR, -2) + 1);
        $return = $this->createPath($prev_path);

        return ($return && is_writable($prev_path)) ? mkdir($path) : false;
    }
}
