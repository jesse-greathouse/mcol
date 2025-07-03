<?php

namespace App;

use App\Exceptions\SettingsIllegalStoreException;
use App\Exceptions\SettingsInvalidPropertyException;
use Illuminate\Contracts\Foundation\Application;

final class Settings
{
    private array $stores = [];

    public function __construct(Application $app)
    {
        $config = $app['config']->get('settings');
        $this->buildStores($config);
    }

    /**
     * Instantiates all the stores.
     */
    private function buildStores(array $config): void
    {
        foreach ($config['stores'] as $key => $store) {
            if (! is_array($store) || ! isset($store['class']) || ! class_exists($store['class'])) {
                throw new SettingsIllegalStoreException("Store: $key has an incorrect configuration.");
            }

            $class = $store['class'];
            $options = (isset($store['options']) && is_array($store['options'])) ? $store['options'] : [];
            $this->stores[$key] = new $class($config['path'], $options);
        }
    }

    /**
     * Returns the stores object as an array.
     */
    public function toArray(): array
    {
        $res = [];

        foreach ($this->stores as $key => $store) {
            $res[$key] = $store->toArray();
        }

        return $res;
    }

    /**
     * Magic function to dynamically check if a property is set.
     */
    public function __isset(string $name): bool
    {
        return isset($this->stores[$name]);
    }

    /**
     * Magic function to retrieve stores as poperties of a Settings instance.
     */
    public function __get(string $name): mixed
    {
        if (isset($this->stores[$name])) {
            return $this->stores[$name];
        } else {
            throw new SettingsInvalidPropertyException("Accessed invalid settings property: $name");
        }
    }

    /**
     * Returns all stores as an associative array.
     */
    public function getStores(): array
    {
        return $this->stores;
    }
}
