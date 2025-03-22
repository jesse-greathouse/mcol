<?php

namespace App\Console\Commands;

use App\SystemMessage,
    App\RabbitMQ\Consumer;

use PhpAmqpLib\Message\AMQPMessage;

use Illuminate\Console\Command;

use Exception;

class SystemMessagesConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:system-messages-consumer {queue} {routingKey?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Real Time System Message consumer';

    /**
     * @var string the name of the routingKey.
     */
    protected $routingKey;

    /**
     * @var string the name of the queue.
     */
    protected $queue;

    /**
     * Execute the console command.
     */
    public function handle(SystemMessage $systemMessage)
    {
        $this->info("Checking System Messages...");

        try {
            $systemMessage->consume(
                $this->getQueue(),
                function(AMQPMessage $msg) {
                    $this->info($msg->getRoutingKey() . ': ' . json_decode($msg->getBody(), true));
                },
                $this->getRoutingKey()

            );
        } catch (Exception $ex) {
            $this->error("StstemMessage Error: " . $ex->getMessage());
        }
    }

    /**
     * Returns the name of the RoutingKey.
     *
     * @return string
     */
    protected function getRoutingKey(): string
    {
        if (null === $this->routingKey) {
            $routingKey = $this->argument('routingKey');

            // Flatten to a string if it's an array.
            if (is_array($routingKey)) {
                $routingKey = implode(' ', $routingKey);
            }

            // If it's null or an empty string, just wildcard the queue.
            if (null === $routingKey || '' === trim($routingKey)) {
                $routingKey = '';
            }

            $this->routingKey = $routingKey;
        }

        return $this->routingKey;
    }


    /**
     * Returns the name of the Queue.
     *
     * @return string
     */
    protected function getQueue(): string
    {
        if (null === $this->queue) {
            $queue = $this->argument('queue');

            // Flatten to a string if it's an array.
            if (is_array($queue)) {
                $queue = implode(' ', $queue);
            }

            // If it's null or an empty string, just wildcard the queue.
            if (null === $queue || '' === trim($queue)) {
                $queue = '';
            }

            $this->queue = $queue;
        }

        return $this->queue;
    }
}
