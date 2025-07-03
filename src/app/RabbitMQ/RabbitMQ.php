<?php

namespace App\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * RabbitMQ Manager: Handles connection, message publishing, and RPC interactions.
 * This class establishes a primary and error-handling connection for enhanced reliability.
 */
final class RabbitMQ
{
    /** @var Connection Primary RabbitMQ connection */
    private readonly Connection $connection;

    /** @var Connection Secondary connection for handling errors */
    private readonly Connection $connectionError;

    /**
     * Initializes RabbitMQ connections.
     *
     * @param  string  $host  RabbitMQ server host.
     * @param  string  $port  Connection port.
     * @param  string  $username  Username for authentication.
     * @param  string  $password  Password for authentication.
     * @param  string  $vhost  Virtual host.
     */
    public function __construct(
        private readonly string $host,
        private readonly string $port,
        private readonly string $username,
        private readonly string $password,
        private readonly string $vhost
    ) {
        $this->connection = new Connection($host, $port, $username, $password, $vhost);
        $this->connectionError = new Connection($host, $port, $username, $password, $vhost);
    }

    /**
     * Establishes connections to both the primary and error queues.
     *
     * @param  string  $queue  Queue name.
     */
    public function createConnect(string $queue): void
    {
        $this->connection->connect($queue);
        $this->connectionError->connect("{$queue}_error");
    }

    /**
     * Creates an exchange with optional configurations.
     *
     * @param  string|null  $name  Exchange name.
     * @param  string|null  $type  Exchange type.
     * @param  bool|null  $passive  Passive mode.
     * @param  bool|null  $durable  Durable flag.
     * @param  bool|null  $autoDelete  Auto-delete flag.
     * @param  bool|null  $internal  Internal exchange.
     * @param  bool|null  $wait  Wait flag.
     * @param  array|null  $properties  Additional properties.
     */
    public function createExchange(
        string $name,
        ?string $type = 'topic',
        ?bool $passive = false,
        ?bool $durable = false,
        ?bool $autoDelete = false,
        ?bool $internal = false,
        ?bool $wait = false,
        ?array $properties = []
    ): void {
        (new Exchange($this->connection))($name, $type, $passive, $durable, $autoDelete, $internal, $wait, $properties);
        (new Exchange($this->connectionError))("{$name}_error", $type, $passive, $durable, $autoDelete, $internal, $wait, $properties);
    }

    /**
     * Publishes a message to the specified exchange.
     *
     * @param  string  $queue  Target queue.
     * @param  string  $exchange  Exchange name.
     * @param  string  $routingKey  Routing key.
     * @param  string  $message  Message body.
     */
    public function publishMessage(string $queue, string $exchange, string $routingKey, string $message): void
    {
        (new Publisher($this->connection))($queue, $exchange, $routingKey, $message);
    }

    /**
     * Sends an RPC request and optionally retries upon failure.
     *
     * @param  string  $id  Unique request identifier.
     * @param  string  $queue  Target queue.
     * @param  string  $queueReturn  Return queue.
     * @param  string  $exchange  Exchange name.
     * @param  string  $routingKey  Routing key.
     * @param  string  $message  Message body.
     * @param  bool  $resend  Whether to resend on failure.
     * @return mixed|null Response data or null if a resend occurred.
     */
    public function requestRpc(
        string $id,
        string $queue,
        string $queueReturn,
        string $exchange,
        string $routingKey,
        string $message,
        bool $resend = false
    ): mixed {
        $rpcRequest = new RequestRPC($this->connection, $queueReturn);
        $result = $rpcRequest->call($id, $queue, $queueReturn, $exchange, $routingKey, $message);

        if ($result instanceof AMQPMessage) {
            $this->connection->shutdown();
            if ($resend) {
                $rpcRequest->resend(
                    $this->connectionError,
                    $result->body,
                    "{$queue}_error",
                    "{$exchange}_error",
                    $routingKey,
                    $queueReturn
                );
            }

            return null;
        }

        $this->connection->shutdown();

        return $result;
    }

    /**
     * Sends an RPC response.
     *
     * @param  array  $message  Response message.
     * @param  AMQPMessage  $AMQPMessage  Original request message.
     */
    public function responseRpc(array $message, AMQPMessage $AMQPMessage): void
    {
        (new RequestRPC($this->connection, $AMQPMessage->get('reply_to')))->response($message, $AMQPMessage);
        $this->connection->shutdown();
    }

    /**
     * Get the connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
