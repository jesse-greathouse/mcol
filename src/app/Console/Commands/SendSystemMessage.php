<?php

namespace App\Console\Commands;

use App\SystemMessage;
use Exception;
use Illuminate\Console\Command;

class SendSystemMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:send-system-message {message}';

    /**
     * @var string message.
     */
    protected $message;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a system message.';

    /**
     * Execute the console command.
     */
    public function handle(SystemMessage $systemMessage)
    {
        $this->info('Sending System Message...');

        $message = $this->getMessage();
        $routingKey = 'mcol';

        try {
            $systemMessage->send($message, $routingKey);
        } catch (Exception $ex) {
            $this->error('Error Sending System Message: '.$ex->getMessage());
        }
    }

    /**
     * Returns the Message.
     */
    protected function getMessage(): ?string
    {
        if ($this->message === null) {
            $message = $this->argument('message');

            if ($message === null || trim($message) === '') {
                $this->error('A valid message is required.');

                return null;
            }

            if (is_array($message)) {
                $message = implode(' ', $message);
            }

            $this->message = $message;
        }

        return $this->message;
    }
}
