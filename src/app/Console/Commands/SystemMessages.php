<?php

namespace App\Console\Commands;

use App\RabbitMQ\Queue;
use App\SystemMessage;
use Illuminate\Console\Command;
use Throwable;

class SystemMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:system-messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recieves all available system messages.';

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
    public function handle(SystemMessage $systemMessage): void
    {
        $this->info('Checking System Messages...');

        try {
            $count = 0;
            $queue = Queue::SYSTEM_MESSAGE_CHAT;

            foreach ($systemMessage->fetch($queue) as $msg) {
                $txt = json_decode($msg->getBody(), true, 512, JSON_THROW_ON_ERROR);
                $this->info("Incoming message: $txt");
                $count++;
            }

            $this->info("Total messages processed: $count");

        } catch (Throwable $ex) {
            $this->error('Error Checking System Messages: '.$ex->getMessage());
        }
    }

    /**
     * Returns the name of the RoutingKey.
     */
    protected function getRoutingKey(): string
    {
        if ($this->routingKey === null) {
            $routingKey = $this->argument('routingKey');

            // Flatten to a string if it's an array.
            if (is_array($routingKey)) {
                $routingKey = implode(' ', $routingKey);
            }

            // If it's null or an empty string, just wildcard the queue.
            if ($routingKey === null || trim($routingKey) === '') {
                $routingKey = '';
            }

            $this->routingKey = $routingKey;
        }

        return $this->routingKey;
    }

    /**
     * Returns the name of the Queue.
     */
    protected function getQueue(): string
    {
        if ($this->queue === null) {
            $queue = $this->argument('queue');

            // Flatten to a string if it's an array.
            if (is_array($queue)) {
                $queue = implode(' ', $queue);
            }

            // If it's null or an empty string, just wildcard the queue.
            if ($queue === null || trim($queue) === '') {
                $queue = '';
            }

            $this->queue = $queue;
        }

        return $this->queue;
    }
}
