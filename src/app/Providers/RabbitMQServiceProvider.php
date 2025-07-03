<?php

namespace App\Providers;

use App\RabbitMQ\RabbitMQ;
use Illuminate\Support\ServiceProvider;

/**
 * RabbitMQ Service Provider
 *
 * This class registers the RabbitMQ service into the Laravel container,
 * allowing for easy dependency injection and configuration using environment variables.
 */
class RabbitMQServiceProvider extends ServiceProvider
{
    /**
     * Registers the RabbitMQ service in the Laravel container.
     *
     * This service is bound as a singleton to ensure only one instance
     * exists within the application lifecycle.
     */
    public function register(): void
    {
        $this->app->singleton(RabbitMQ::class, function ($app) {
            return new RabbitMQ(
                host: env('RABBITMQ_HOST', 'localhost'),
                port: (int) env('RABBITMQ_PORT', 5672),
                username: env('RABBITMQ_USERNAME', 'guest'),
                password: env('RABBITMQ_PASSWORD', 'guest'),
                vhost: env('RABBITMQ_VHOST', '/')
            );
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
