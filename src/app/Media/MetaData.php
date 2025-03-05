<?php

namespace App\Media;

use BadMethodCallException;

/**
 * A container for storing and retrieving metadata key-value pairs.
 *
 * This class provides dynamic getter, setter, and builder-style methods through __call(),
 * allowing properties to be accessed using `get<Key>`, `set<Key>`, and `with<Key>` conventions.
 *
 * @package App\Media
 */
class MetaData
{
    /** @var array<string, string> Stores metadata as key-value pairs */
    protected array $data = [];

    /**
     * Static factory method to create a new instance.
     *
     * @return static New instance of MetaData.
     */
    public static function build(): static
    {
        return new static();
    }

    /**
     * Magic method to handle dynamic getters, setters, and builder methods.
     *
     * Supports:
     * - `set<Key>(<value>)` to set metadata values.
     * - `get<Key>()` to retrieve metadata values.
     * - `with<Key>(<value>)` to set metadata values and return $this for chaining.
     *
     * @param string $name The called method name.
     * @param array $arguments Arguments passed to the method.
     *
     * @throws BadMethodCallException If the method name does not follow the expected pattern.
     * @return mixed Returns the requested metadata value for getter calls or $this for chaining.
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (str_starts_with($name, 'set')) {
            $this->setData($this->extractKey($name, 'set'), $arguments[0] ?? null);
            return true;
        }

        if (str_starts_with($name, 'get')) {
            return $this->getData($this->extractKey($name, 'get'));
        }

        if (str_starts_with($name, 'with')) {
            return $this->withData($this->extractKey($name, 'with'), $arguments[0] ?? null);
        }

        throw new BadMethodCallException("Method '{$name}' does not exist.");
    }

    /**
     * Stores a metadata key-value pair.
     *
     * @param string $key The metadata key.
     * @param string|null $value The value to be associated with the key.
     */
    protected function setData(string $key, ?string $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Retrieves the value of a metadata key.
     *
     * @param string $key The metadata key.
     *
     * @throws BadMethodCallException If the requested key does not exist.
     * @return string|null The value associated with the key.
     */
    protected function getData(string $key): string|null
    {
        if (!array_key_exists($key, $this->data)) {
            throw new BadMethodCallException("Metadata key '{$key}' does not exist.");
        }

        return $this->data[$key];
    }

    /**
     * Stores a metadata key-value pair using setData and returns $this for method chaining.
     *
     * @param string $key The metadata key.
     * @param string|null $value The value to be associated with the key.
     * @return static Returns the current instance for chaining.
     */
    protected function withData(string $key, ?string $value): static
    {
        $this->setData($key, $value);
        return $this;
    }

    /**
     * Extracts the metadata key from a method name.
     *
     * @param string $method The full method name (e.g., 'getTitle').
     * @param string $prefix The prefix to remove ('get', 'set', or 'with').
     *
     * @return string The extracted key, converted to lowercase.
     */
    protected function extractKey(string $method, string $prefix): string
    {
        return strtolower(substr($method, strlen($prefix)));
    }
}
