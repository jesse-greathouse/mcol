<?php

namespace App\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel,
    PhpAmqpLib\Message\AMQPMessage;

use Exception,
    RuntimeException;

/**
 * Class RequestRPC
 * Handles RPC-based communication using RabbitMQ.
 * Supports message sending, receiving responses, and error handling.
 */
final class RequestRPC
{
    /** @var AMQPChannel|null RabbitMQ communication channel */
    private ?AMQPChannel $channel;

    /** @var Connection RabbitMQ connection instance */
    private Connection $connection;

    /** @var mixed|null Stores the response from the queue */
    private mixed $response = null;

    /** @var string Unique correlation ID for message tracking */
    private string $id;

    /** @var string Callback queue name */
    private string $callbackQueue;

    /**
     * RequestRPC constructor.
     * Initializes the RabbitMQ channel and sets up a consumer for responses.
     */
    public function __construct(Connection $connection, string $callbackQueue)
    {
        $this->connection = $connection;
        $this->channel = $this->connection->getChannel();
        $this->callbackQueue = $this->declareQueue($callbackQueue);

        $this->channel->basic_consume(
            $this->callbackQueue,
            '',
            false,
            false,
            false,
            false,
            [$this, 'messageReceived']
        );
    }

    /**
     * Declares a queue and returns its name.
     */
    private function declareQueue(string $queueName): string
    {
        [$queueName, ,] = $this->channel->queue_declare($queueName, false, false, false, false);
        return $queueName;
    }

    /**
     * Callback for receiving messages.
     */
    public function messageReceived(AMQPMessage $message): void
    {
        if ($message->get('correlation_id') !== $this->id) {
            $message->ack();
            return;
        }

        $message->ack();
        $this->response = $message->getBody();
    }

    /**
     * Sends an RPC request and waits for a response.
     */
    public function call(
        string $id,
        string $queue,
        string $queueReturn,
        string $exchange,
        string $routingKey,
        string $message
    ): mixed {
        $this->id = $id;
        $this->response = null;
        $this->callbackQueue = $queueReturn;

        $this->channel->queue_bind($queue, $exchange, $routingKey);

        $msg = new AMQPMessage(
            $message,
            [
                'correlation_id' => $id,
                'reply_to' => $queueReturn
            ]
        );

        $this->channel->basic_publish($msg, $exchange, $routingKey);

        while ($this->response === null) {
            $this->channel->wait();
        }

        return $this->response;
    }

    /**
     * Sends a response message to the reply-to queue.
     */
    public function response(array $message, AMQPMessage $requestMessage): void
    {
        try {
            $responseMessage = new AMQPMessage(
                json_encode($message),
                ['correlation_id' => $requestMessage->get('correlation_id')]
            );

            $requestMessage->get('channel')->basic_publish(
                $responseMessage,
                '',
                $requestMessage->get('reply_to')
            );
        } catch (Exception $ex) {
            throw new RuntimeException("Error in response message: " . $ex->getMessage());
        }
    }

    /**
     * Resends a message in case of an error.
     */
    public function resend(
        Connection $connectionError,
        string $message,
        string $queueError,
        string $exchange,
        string $routingKey
    ): void {
        try {
            $channelError = $connectionError->getChannel();
            $channelError->queue_bind($queueError, $exchange, $routingKey);

            $publisherError = new Publisher($connectionError);
            $publisherError($queueError, $exchange, $routingKey, $message);
        } catch (Exception $ex) {
            throw new RuntimeException("Error in resending message: " . $ex->getMessage());
        } finally {
            $connectionError->shutdown();
        }
    }
}
