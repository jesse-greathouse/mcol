<?php

namespace App\Providers;

use App\SystemMessage,
    App\RabbitMQ\RabbitMQ;

use Illuminate\Support\ServiceProvider;

/**
 * SystemMessage Service Provider
 *
 * Right now this class is a pretty thin wrapper for RabbitMQ.
 * I only wanted to include this class on the idea to Formaize a system message,
 * Using RabbitMQ, it allows messages to be passwed between different processes.
 */
class SystemMessageProvider extends ServiceProvider
{
    /**
     * Registers the SystemMessage service in the Laravel container.
     *
     * This service is bound as a singleton to ensure only one instance
     * exists within the application lifecycle.
     */
    public function register(): void
    {
        $this->app->singleton(RabbitMQ::class, function ($app) {
            return new SystemMessage($app->make(RabbitMQ::class));
        });
    }

    /**
     * Provides the list of services bound by this provider.
     *
     * @return array<int, string> The list of service names.
     */
    public function provides(): array
    {
        return [RabbitMQ::class];
    }
}
