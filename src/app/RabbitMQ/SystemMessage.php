<?php

namespace App\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Handles incoming system messages from RabbitMQ.
 *
 * This class processes messages, logs them, and optionally
 * invokes a user-defined callback for additional handling.
 */
class SystemMessage implements MessageInterface
{
    /** @var string The name of the RabbitMQ queue. */
    public const Queue = 'system';

    /** @var string The channel name for command messages. */
    public const TOPIC = 'messages';

    /** @var ?callable A callback function for handling messages. */
    private $callback = null;

    /**
     * Constructs the SystemMessage instance.
     *
     * @param ?callable $callback Optional callback function for message handling.
     */
    public function __construct(?callable $callback = null)
    {
        $this->callback = $callback;
    }

    /**
     * Handles an incoming RabbitMQ message.
     *
     * @param AMQPMessage $message The received AMQP message.
     */
    public function handleMessage(AMQPMessage $message): void
    {
        if ($this->callback) {
            ($this->callback)($message);
        }
    }
}
