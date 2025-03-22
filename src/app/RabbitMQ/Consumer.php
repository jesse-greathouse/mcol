<?php

namespace App\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel,
    PhpAmqpLib\Message\AMQPMessage;

use Generator,
    RuntimeException;

/**
 * Class Consumer
 *
 * Handles message consumption from RabbitMQ queues using AMQP.
 * This class binds a queue to an exchange, configures QoS settings,
 * and processes incoming messages using a provided message handler.
 */
final class Consumer
{
    /**
     * AMQP channel for handling message consumption.
     */
    private readonly ?AMQPChannel $channel;

    /**
     * Connection instance for managing the RabbitMQ connection.
     */
    private readonly Connection $connection;

    /**
     * Message processing handler.
     */
    private readonly MessageInterface $messageObject;

    /**
     * Consumer constructor.
     *
     * @param Connection $connection The RabbitMQ connection instance.
     * @param MessageInterface $messageObject The message handler instance.
     */
    public function __construct(Connection $connection, MessageInterface $messageObject)
    {
        $this->connection = $connection;
        $this->channel = $this->connection->getChannel();
        $this->messageObject = $messageObject;
    }

    /**
     * Invokes the consumer to bind a queue, configure QoS, and consume messages.
     *
     * @param string $queue The queue name.
     * @param string $exchange The exchange name.
     * @param string $routingKey The routing key.
     * @param array $arguments The arguments that will be used during the exchange.
     */
    public function __invoke(string $queue = '', string $exchange = '', string $routingKey = '', array $arguments = []): void
    {
        if (!$this->channel) {
            throw new RuntimeException('AMQP Channel is not initialized.');
        }

        $this->channel->queue_bind($queue, $exchange, $routingKey);
        $this->channel->basic_qos(null, 1, null);

        $this->channel->basic_consume(
            queue: $queue,
            consumer_tag: '',
            no_local: false,
            no_ack: false,
            exclusive: false,
            nowait: false,
            arguments: $arguments,
            callback: fn(AMQPMessage $msg) => $this->handleMessage($msg, $routingKey)
        );

        while (!empty($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    /**
     * The consumer will fetch all the available messages.
     *
     * @param string $queue The queue name.
     * @param string $exchange The exchange name.
     * @param string $routingKey The routing key.
     * @param int $limit The maximum number of messages to fetch.
     * @return Generator
     */
    public function fetch(string $queue = '', string $exchange = '', string $routingKey = '', int $limit = 10): Generator
    {
        if (!$this->channel) {
            throw new RuntimeException('AMQP Channel is not initialized.');
        }

        $this->channel->queue_bind($queue, $exchange, $routingKey);
        $this->channel->basic_qos(null, $limit, null);

        while ($msg = $this->channel->basic_get($queue)) {
            $msg->ack();
            yield $msg;
        }
    }

    /**
     * Handles an incoming AMQP message and acknowledges it.
     *
     * @param AMQPMessage $msg The received message.
     */
    private function handleMessage(AMQPMessage $msg, string $routingKey): void
    {
        $msg->ack();
        $this->messageObject->handleMessage($msg);
    }

    public static function messageMatchesRoutingKey(AMQPMessage $msg, string $routingKey): bool
    {
        // Remove any empty space on the ends of the string.
        $routingKey = trim($routingKey);

        // If the routing Key ends with wildcard, trim it off.
        $routingKey = rtrim($routingKey, '*');

        // If the routing Key ends with period, trim it off.
        $routingKey = rtrim($routingKey, '.');

        if (str_starts_with($msg->getRoutingKey(), $routingKey)) {
            return true;
        }

        return false;
    }
}
