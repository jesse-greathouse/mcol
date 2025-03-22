<?php

namespace App;

use App\Exceptions\IllegalSystemMessageQueueException,
    App\Exceptions\MessageBrokerException,
    App\RabbitMQ\Connection,
    App\RabbitMQ\Consumer,
    App\RabbitMQ\Queue,
    App\RabbitMQ\RabbitMQ,
    App\RabbitMQ\SystemMessage as Message;

use Generator,
    RuntimeException,
    Throwable;

/**
 * Handles System Messaging operations, including fetching, consuming, and sending messages.
 *
 * This class provides an interface for interacting with a RabbitMQ message queue
 * to process system messages efficiently.
 */
final class SystemMessage
{
    /** @var RabbitMQ The RabbitMQ instance used for messaging operations */
    public function __construct(protected RabbitMQ $rabbitMQ) {}

    /**
     * Fetches system messages from the queue.
     *
     * @param string $queue The name of the queue to fetch from.
     * @param string $routingKey the routing key to apply.
     * @return @return Generator
     * @throws MessageBrokerException Below this layer the MessageBrokerException will be thrown.
     */
    public function fetch(string $queue, string $routingKey = ''): Generator
    {
        $routingKeys = (!empty($routingKeys)) ? $routingKeys : ['*']; // Default wildcard.

        try {
            [ $connection, $queue, $exchange, $routingKey ] = $this->makeConnection($queue, $routingKey);

            if (!$connection) {
                throw new RuntimeException('Failed to establish a RabbitMQ connection.');
            }

            return (new Consumer($connection, new Message()))->fetch($queue, $exchange, $routingKey);
        } catch (Throwable $e) {
            throw new MessageBrokerException("Error fetching messages: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Consumes system messages in real time, processing them via the provided callback.
     *
     * @param callable|null $callback Optional callback function for message handling.
     * @param string $queue The name of the queue to fetch from.
     * @param string|null   $routingKey Optional routing key to filter messages.
     * @param array         $arguments The arguments that will be used during the exchange.
     * @return void
     */
    public function consume(string $queue, ?callable $callback = null, ?string $routingKey = '', array $arguments = []): void
    {
        try {
            [ $connection, $queue, $exchange, $routingKey ] = $this->makeConnection($queue, $routingKey);

            if (!$connection) {
                throw new RuntimeException('Failed to establish a RabbitMQ connection.');
            }

            // Creates a persistent loop over the Message queue topic (With routing key if specified).
            (new Consumer($connection, new Message($callback)))
                ->__invoke($queue, $exchange, $routingKey, $arguments);
        } catch (Throwable $e) {
            throw new MessageBrokerException("Error consuming messages: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Sends a message to the system queue.
     *
     * @param string $message The message content.
     * @param string $queue The name of the queue to fetch from.
     * @param string $routingKey Optional routing key for message routing.
     * @return void
     */
    public function send(string $message, string $queue, ?string $routingKey = ''): void
    {
        if (!$message) {
            return;
        }

        try {
            [ , $queue, $exchange, $routingKey ] = $this->makeConnection($queue, $routingKey);

            $this->rabbitMQ->publishMessage(
                $queue,
                $exchange,
                $routingKey,
                json_encode($message, JSON_THROW_ON_ERROR)
            );
        } catch (Throwable $e) {
            throw new MessageBrokerException("Error sending message: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Establishes a connection to a RabbitMQ queue, ensuring the queue and exchange are properly configured.
     *
     * This method validates that the specified queue exists in the system's exchange map, constructs
     * a fully qualified queue name, and derives a routing key. It then initializes the RabbitMQ connection
     * and declares the necessary exchange.
     *
     * @param string $queue       The name of the queue to connect to.
     * @param string $routingKey  (Optional) A routing key to use for message routing. Defaults to an empty string.
     * @param string $type        (Optional) The exchange type (e.g., "topic", "direct"). Defaults to "topic".
     *
     * @throws IllegalSystemMessageQueueException If the specified queue is not defined in the exchange map.
     *
     * @return array{object, string, string, string} Returns an array containing:
     *   - The fully qualified queue name.
     *   - The exchange name.
     *   - The computed routing key.
     *   - The RabbitMQ connection object.
     */
    private function makeConnection(string $queue, string $routingKey = '', string $type = "topic"): array
    {
        if (!in_array($queue, array_keys(Queue::EXCHANGE_MAP))) {
            throw new IllegalSystemMessageQueueException("System Message Queue: \"$queue\" does not exist.");
        }

        $exchange = Queue::EXCHANGE_MAP[$queue];
        $queue = "$exchange.$queue";
        $routingKey = $this->makeFullyQualifiedRoutingKey($queue, $routingKey);

        $this->rabbitMQ->createConnect($queue);
        $this->rabbitMQ->createExchange($exchange, $type);
        return [$this->rabbitMQ->getConnection(), $queue, $exchange, $routingKey];
    }

    /**
     * This will prepend the exchange to the routing key
     * routing key: *
     * fully qualified: system.message.*
     *
     * @var string $queue The identifier of the queue
     * @var string $routingKey A sting that describes the domain of messages to recieve.
     */
    private function makeFullyQualifiedRoutingKey(string $queue, string $routingKey)
    {
        $routingKey = $this->stripPrefix($queue, $routingKey);
        return "$queue.$routingKey";
    }

    /**
     * The routing key can technically come in as "fully qualified".
     * This would mean that it starts with the queue and topic.
     * e.g. (system.message.*)
     *
     * Since the downstream code wants to always append the queue.topic,
     * this code will strip it to prevent the inevitable system.message.system.message.*
     *
     * @var string $queue The identifier of the queue
     * @var string $routingKey A sting that describes the domain of messages to recieve.
     */
    private function stripPrefix(string $queue, string $routingKey)
    {
        // Strip prefix if fully qualified $routingKey
        $prefix = "$queue.";
        if (str_starts_with($routingKey, $prefix)) {
            $routingKey = str_replace($routingKey, $prefix, '');
        }

        return $routingKey;
    }
}
