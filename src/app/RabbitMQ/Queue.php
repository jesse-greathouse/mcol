<?php

namespace App\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel,
    PhpAmqpLib\Exception\AMQPRuntimeException;

/**
 * Class Queue
 *
 * Handles RabbitMQ queue operations such as creation, purging, and deletion.
 */
final class Queue
{
    /** @var string System Messages that originate in chat. */
    const SYSTEM_MESSAGE_CHAT = 'chat';

    /** @var array Maps Queues to exchanges. */
    const EXCHANGE_MAP = [
        self::SYSTEM_MESSAGE_CHAT => Exchange::SYSTEM_MESSAGE,
    ];

    /** @var AMQPChannel|null $channel The AMQP channel for queue operations */
    private readonly ?AMQPChannel $channel;

    /**
     * Queue constructor.
     *
     * @param Connection $connection The RabbitMQ connection instance.
     */
    public function __construct(Connection $connection)
    {
        $this->channel = $connection->getChannel();
    }

    /**
     * Declares a queue in RabbitMQ.
     *
     * @param string $queue The name of the queue.
     * @param string $exchange The name of the exchange.
     * @throws AMQPRuntimeException If an error occurs while declaring the queue.
     */
    public function __invoke(string $queue, string $exchange): void
    {
        try {
            $this->channel?->queue_declare($queue, false, true, false, false, false);
        } catch (\Throwable $ex) {
            throw new AMQPRuntimeException("Failed to create queue '{$queue}': " . $ex->getMessage(), 0, $ex);
        }
    }

    /**
     * Purges all messages from the specified queue.
     *
     * @param string $queue The name of the queue to purge.
     * @throws AMQPRuntimeException If an error occurs while purging the queue.
     */
    public function purgeQueue(string $queue): void
    {
        try {
            $this->channel?->queue_purge($queue);
        } catch (\Throwable $ex) {
            throw new AMQPRuntimeException("Failed to purge queue '{$queue}': " . $ex->getMessage(), 0, $ex);
        }
    }

    /**
     * Deletes the specified queue.
     *
     * @param string $queue The name of the queue to delete.
     * @throws AMQPRuntimeException If an error occurs while deleting the queue.
     */
    public function deleteQueue(string $queue): void
    {
        try {
            $this->channel?->queue_delete($queue);
        } catch (\Throwable $ex) {
            throw new AMQPRuntimeException("Failed to delete queue '{$queue}': " . $ex->getMessage(), 0, $ex);
        }
    }
}
