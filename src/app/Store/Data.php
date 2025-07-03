<?php

namespace App\Store;

use Symfony\Component\Yaml\Yaml;

use App\Exceptions\DataStoreUnsupportedFormatException,
    App\Exceptions\DataStoreInvalidStorePathException,
    App\Exceptions\DataStoreUnableToInitializeException,
    App\Exceptions\DataStoreInvalidPropertyException;

abstract class Data
{
    const YAML_FILE_EXTENSION = 'yml';

    /**
     * location of where the data files will be stored.
     *
     * @var string
     */
    protected string $path;

    /**
     * The body of data values that can be stored and retrieved.
     *
     * @var array
     */
    protected array $storable = [];

    /**
     * The configuration of the Yaml Parser.
     *
     * @var array
     */
    protected array $config = [
        'flags'  => 0,
        'inline' => 2,
        'indent' => 4,
    ];

    /**
     * @param string $path
     * @param ?array $config
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
     *
     * @return void
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
        if (self::YAML_FILE_EXTENSION !== $extension) {
            throw new DataStoreUnsupportedFormatException(
                "Data Store: \"$baseName\" has unsupported format. Try with: \"$fileName." . self::YAML_FILE_EXTENSION . "\".");
        }

        if (!$this->createPath($dirName)) {
            throw new DataStoreInvalidStorePathException("Could not create: \"$dirName\" for data store.");
        }

        if (!touch($uri)) {
            throw new DataStoreUnableToInitializeException("Could not initialize the data store: \"$uri\".");
        }
    }

    /**
     * Returns the storable object as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->getStorable();
    }

    /**
     * Returns a list of all the storable keys.
     *
     * @return array
     */
    public function getKeys(): array
    {
        return array_keys($this->storable);
    }

    /**
     * Returns the storable array.
     *
     * @return array
     */
    public function getStorable(): array
    {
        return $this->storable;
    }

    /**
    * Save the storable array into the yaml configuration file.
    *
    * @return void
    */
    public function save(): void
    {
        $yamlStr = Yaml::dump($this->storable, $this->config['inline'], $this->config['indent'], $this->config['flags']);
        file_put_contents($this->path, $yamlStr);
    }

    /**
    * Override the default configuration with any valid keys.
    *
    * @param array $config
    * @return void
    */
    protected function configure(array $config)
    {
        if (0 < count($config)) {
            foreach($this->config as $key => $val) {
                if (isset($config[$key])) {
                    $this->config[$key] = $config[$key];
                }
            }
        }
    }

    /**
    * Magic function to retrieve properties dynamically.
    *
    * @param string $name
    * @return mixed
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
    *
    * @param string $name
    * @param mixed $value
    * @return void
    */
    public function __set(string $name, mixed $value): void
    {
        $this->storable[$name] = $value;
    }

    /**
    * Magic function to dynamically check if a property is set.
    *
    * @param string $name
    * @return boolean
    */
    public function __isset(string $name): bool
    {
        return isset($this->storable[$name]);
    }

    /**
    * Magic function to unset properties dynamically.
    *
    * @param string $name
    * @return void
    */
    public function __unset(string $name): void
    {
        unset($this->storable[$name]);
    }


    /**
     * Get location of where the data files will be stored.
     *
     * @return  string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set location of where the data files will be stored.
     *
     * @param  string  $path  location of where the data files will be stored.
     *
     * @return void
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Make the file path work recursively
     *
     * @param  string  $path  location of where the data files will be stored.
     * @return bool
     */
    protected function createPath(string $path): bool
    {
        if (is_dir($path)) return true;

        $prev_path = substr($path, 0, strrpos($path, DIRECTORY_SEPARATOR, -2) + 1 );
        $return = $this->createPath($prev_path);
        return ($return && is_writable($prev_path)) ? mkdir($path) : false;
    }
}
