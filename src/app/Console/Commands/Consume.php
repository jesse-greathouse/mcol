<?php

namespace App\Console\Commands;

use App\RabbitMQ\Connection;
use App\RabbitMQ\Consumer;
use App\RabbitMQ\Queue;
use App\RabbitMQ\SystemMessage;
use Exception;
use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;

class Consume extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:consume {queue} {topic} {routingKey?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Real Time RabbitMQ consumer';

    /**
     * @var string the name of the topic.
     */
    protected $topic;

    /**
     * @var string the name of the queue.
     */
    protected $queue;

    /**
     * @var string the name of the routingKey.
     */
    protected $routingKey;

    /**
     * @var array A List of allowed Queues to be used with this tool.
     */
    protected $queueWhitelist = [
        Queue::SYSTEM_MESSAGE_CHAT,
    ];

    /**
     * @var array A List of allowed Topics to be used with this tool.
     */
    protected $topicWhitelist = [
        SystemMessage::TOPIC,
    ];

    /**
     * @var array A List of allowed Queues to be used with this tool.
     */
    protected $topicClassMap = [
        SystemMessage::TOPIC => SystemMessage::class,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $this->info('Consuming System Messages...');

        try {
            $connection = new Connection(
                env('RABBITMQ_HOST', 'localhost'),
                env('RABBITMQ_PORT', '5667'),
                env('RABBITMQ_USERNAME', 'rabbitmq'),
                env('RABBITMQ_PASSWORD', 'rabbitmq'),
                env('RABBITMQ_VHOST', '/')
            );

            $queue = $this->getQueue();
            $topic = $this->getTopic();

            if (! $queue || ! $topic) {
                $this->error('A valid --queue and --topic are required.');

                return;
            }

            $routingKey = $this->getRoutingKey();
            $messageClass = $this->topicClassMap[$topic];

            $connection->connect($queue);

            // Creates a persistent loop over the Message queue topic (With routing key if specified).
            (new Consumer($connection, new $messageClass(
                fn (AMQPMessage $message) => $this->info(json_decode($message->getBody(), true))
            )))->__invoke($queue, $topic, $routingKey);

        } catch (Exception $ex) {
            $this->error('Error Consuming System Messages: '.$ex->getMessage());
        }
    }

    /**
     * Returns the name of the Queue.
     */
    protected function getQueue(): ?string
    {
        if ($this->queue === null) {
            $queue = $this->argument('queue');

            // Flatten to a string if it's an array.
            if (is_array($queue)) {
                $queue = implode(' ', $queue);
            }

            if ($queue === null || trim($queue) === '') {
                $this->error('A valid queue is required.');

                return null;
            }

            if (! in_array($queue, $this->queueWhitelist)) {
                $list = implode(', ', $this->queueWhitelist);
                $this->error("Unable to query for queue: $queue. (Available queues are: $list)");

                return null;
            }

            $this->queue = $queue;
        }

        return $this->queue;
    }

    /**
     * Returns the name of the Topic.
     */
    protected function getTopic(): ?string
    {
        if ($this->topic === null) {
            $topic = $this->argument('topic');

            // Flatten to a string if it's an array.
            if (is_array($topic)) {
                $topic = implode(' ', $topic);
            }

            if ($topic === null || trim($topic) === '') {
                $this->error('A valid topic is required.');

                return null;
            }

            if (! in_array($topic, $this->topicWhitelist)) {
                $list = implode(', ', $this->topicWhitelist);
                $this->error("Unable to query for topic: $topic. (Available topics are: $list)");

                return null;
            }

            $this->topic = $topic;
        }

        return $this->topic;
    }

    /**
     * Returns the name of the RoutingKey.
     */
    protected function getRoutingKey(): ?string
    {
        if ($this->routingKey === null) {
            $routingKey = $this->argument('routingKey');

            // Flatten to a string if it's an array.
            if (is_array($routingKey)) {
                $routingKey = implode(' ', $routingKey);
            }

            // If it's null or an empty string, just wildcard the queue.
            if ($routingKey === null || trim($routingKey) === '') {
                $queue = $this->getQueue();

                if (! $queue) {
                    return null;
                }

                $routingKey = "$queue.*";
            }

            $this->routingKey = $routingKey;
        }

        return $this->routingKey;
    }
}
