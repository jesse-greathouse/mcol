<?php

namespace App\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use RuntimeException;

/**
 * Handles a connection to a RabbitMQ server, managing queue declarations and messaging channels.
 *
 * This class abstracts the connection and channel creation logic, ensuring efficient
 * interaction with RabbitMQ. It provides methods for establishing a connection,
 * retrieving the active channel, and shutting down gracefully.
 */
final class Connection
{
    /** @var string The RabbitMQ server hostname or IP address. */
    private readonly string $host;

    /** @var string The RabbitMQ server port. */
    private readonly string $port;

    /** @var string The username for RabbitMQ authentication. */
    private readonly string $username;

    /** @var string The password for RabbitMQ authentication. */
    private readonly string $password;

    /** @var string The virtual host to connect to on the RabbitMQ server. */
    private readonly string $vhost;

    /** @var string|null The name of the queue being used. */
    private ?string $queue = null;

    /** @var AMQPStreamConnection|null The active RabbitMQ connection instance. */
    private ?AMQPStreamConnection $connection = null;

    /** @var AMQPChannel|null The active RabbitMQ channel. */
    private ?AMQPChannel $channel = null;

    /**
     * @param  string  $host  RabbitMQ host
     * @param  string  $port  RabbitMQ port
     * @param  string  $username  RabbitMQ username
     * @param  string  $password  RabbitMQ password
     * @param  string  $vhost  RabbitMQ virtual host
     */
    public function __construct(
        string $host,
        string $port,
        string $username,
        string $password,
        string $vhost
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->vhost = $vhost;
    }

    /**
     * Establishes a connection to RabbitMQ and declares a queue.
     *
     * @param  string  $queue  The queue name.
     * @return AMQPStreamConnection|null Returns the connection or null if an error occurs.
     */
    public function connect(string $queue): ?AMQPStreamConnection
    {
        $this->queue = $queue;

        try {
            $this->connection = new AMQPStreamConnection(
                $this->host,
                (int) $this->port, // Ensure port is treated as integer
                $this->username,
                $this->password,
                $this->vhost
            );

            $this->channel = $this->connection->channel();
            $this->channel->queue_declare($this->queue, false, true, false, false);

            return $this->connection;
        } catch (AMQPRuntimeException $exception) {
            error_log('RabbitMQ Connection Error: '.$exception->getMessage());

            return null;
        }
    }

    /**
     * Returns the active connection instance.
     *
     * @return AMQPStreamConnection The active connection.
     *
     * @throws RuntimeException If the connection has not been established.
     */
    public function getConnection(): AMQPStreamConnection
    {
        return $this->connection
            ?? throw new RuntimeException('No active RabbitMQ connection.');
    }

    /**
     * Returns the active channel.
     *
     * @return AMQPChannel The active channel.
     *
     * @throws RuntimeException If no channel has been created.
     */
    public function getChannel(): AMQPChannel
    {
        return $this->channel
            ?? throw new RuntimeException('No active RabbitMQ channel.');
    }

    /**
     * Returns the name of the queue.
     *
     * @return string The queue name.
     *
     * @throws RuntimeException If the queue has not been set.
     */
    public function getQueue(): string
    {
        return $this->queue
            ?? throw new RuntimeException('Queue name has not been set.');
    }

    /**
     * Closes the channel and the connection.
     */
    public function shutdown(): void
    {
        if ($this->channel) {
            $this->channel->close();
        }

        if ($this->connection) {
            try {
                $this->connection->close();
            } catch (AMQPRuntimeException $exception) {
                error_log('RabbitMQ Shutdown Error: '.$exception->getMessage());
            }
        }
    }
}
