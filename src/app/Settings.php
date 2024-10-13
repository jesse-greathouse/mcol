<?php

namespace App;

use Illuminate\Contracts\Foundation\Application;

use App\Exceptions\SettingsIllegalStoreException,
    App\Exceptions\SettingsInvalidPropertyException;

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
     *
     * @param array $config
     * @return void
     */
    private function buildStores(array $config): void
    {
        forEach($config['stores'] as $key => $store) {
            if (!is_array($store) || !isset($store['class']) || !class_exists($store['class'])) {
                throw new SettingsIllegalStoreException("Store: $key has an incorrect configuration.");
            }

            $class = $store['class'];
            $options = (isset($store['options']) && is_array($store['options'])) ? $store['options'] : [];
            $this->stores[$key] = new $class($config['path'], $options);
        }
    }

    /**
     * Returns the stores object as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $res = [];

        foreach($this->stores as $key => $store) {
            $res[$key] = $store->toArray();
        }

        return $res;
    }

    /**
    * Magic function to retrieve stores as poperties of a Settings instance.
    *
    * @param string $name
    * @return mixed
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
     *
     * @return array
     */
    public function getStores(): array
    {
        return $this->stores;
    }
}
