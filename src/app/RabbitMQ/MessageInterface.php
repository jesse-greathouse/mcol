<?php

namespace App\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Interface MessageInterface
 *
 * Defines the contract for handling incoming RabbitMQ messages.
 */
interface MessageInterface
{
    /**
     * Handles an incoming RabbitMQ message.
     *
     * @param AMQPMessage $message The received AMQP message.
     */
    public function handleMessage(AMQPMessage $message): void;
}
