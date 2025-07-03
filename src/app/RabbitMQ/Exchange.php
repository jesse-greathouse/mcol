<?php

namespace App\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPRuntimeException;

/**
 * Class Exchange
 *
 * Represents an AMQP exchange in RabbitMQ, managing its declaration
 * and configuration using a provided AMQP channel.
 */
final class Exchange
{
    const SYSTEM_MESSAGE = 'system.message';

    /** @var AMQPChannel The AMQP channel used for exchange operations */
    private readonly AMQPChannel $channel;

    /**
     * Exchange constructor.
     *
     * @param  Connection  $connection  The RabbitMQ connection instance.
     *
     * @throws \RuntimeException If no valid AMQP channel is available.
     */
    public function __construct(Connection $connection)
    {
        $channel = $connection->getChannel();
        if (! $channel instanceof AMQPChannel) {
            throw new \RuntimeException('Error: No valid connection available');
        }

        $this->channel = $channel;
    }

    /**
     * Declare an exchange in RabbitMQ.
     *
     * @param  string  $exchange  Exchange name.
     * @param  string  $type  Exchange type (direct, fanout, topic, headers).
     * @param  bool  $passive  Whether to check if the exchange exists without modifying it.
     * @param  bool  $durable  Whether the exchange survives a broker restart.
     * @param  bool  $autoDelete  Whether the exchange is deleted when no queues are bound.
     * @param  bool  $internal  Whether the exchange is used only internally by RabbitMQ.
     * @param  bool  $wait  Whether the server should respond to the declaration.
     * @param  array  $properties  Additional properties for the exchange.
     *
     * @throws AMQPException If an error occurs while declaring the exchange.
     */
    public function __invoke(
        string $exchange,
        string $type,
        bool $passive = false,
        bool $durable = true,
        bool $autoDelete = false,
        bool $internal = false,
        bool $wait = true,
        array $properties = []
    ): void {
        try {
            $this->channel->exchange_declare(
                $exchange,
                $type,
                $passive,
                $durable,
                $autoDelete,
                $internal,
                $wait,
                $properties
            );
        } catch (AMQPRuntimeException $ex) {
            throw new AMQPRuntimeException('Error creating exchange: '.$ex->getMessage(), $ex->getCode(), $ex);
        }
    }
}
