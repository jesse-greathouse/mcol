<?php

namespace App\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel,
    PhpAmqpLib\Message\AMQPMessage;

use RuntimeException;

/**
 * Handles publishing messages to RabbitMQ queues with exception safety.
 */
final class Publisher
{
    /**
     * The AMQP channel used for communication.
     */
    private readonly ?AMQPChannel $channel;

    /**
     * The RabbitMQ connection instance.
     */
    private readonly Connection $connection;

    /**
     * Initializes the publisher with an existing RabbitMQ connection.
     *
     * @param Connection $connection The RabbitMQ connection instance.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->channel = $connection->getChannel();
    }

    /**
     * Publishes a message to the specified queue and exchange.
     *
     * @param string $queue The queue name.
     * @param string $exchange The exchange name.
     * @param string $routingKey The routing key for message delivery.
     * @param string $message The message content.
     *
     * @throws RuntimeException If an error occurs during publishing.
     */
    public function __invoke(
        string $queue = "",
        string $exchange = "",
        string $routingKey = "anonymous.info",
        string $message = ""
    ): void {
        if ($this->channel === null) {
            throw new RuntimeException("AMQP channel is not initialized.");
        }

        try {
            $this->channel->queue_bind($queue, $exchange, $routingKey);
            $this->channel->basic_publish(
                new AMQPMessage($message, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]),
                $exchange,
                $routingKey
            );
        } catch (\Throwable $ex) {
            throw new RuntimeException("Publishing error: " . $ex->getMessage(), previous: $ex);
        } finally {
            $this->connection->shutdown();
        }
    }
}
