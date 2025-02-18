<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\User;

class GenerateApiToken extends Command
{
    /** @var User|null Selected user instance */
    protected ?User $user = null;

    /** @var string The name and signature of the console command */
    protected $signature = 'mcol:generate-api-token {email}';

    /** @var string The console command description */
    protected $description = 'Creates an API token for a user by a given email address.';

    /**
     * Execute the console command.
     *
     * @return int Exit status code
     */
    public function handle(): int
    {
        $user = $this->getUser();

        if ($user === null) {
            return Command::FAILURE;
        }

        $token = $user->createToken('bearer_token');
        $this->warn($token->plainTextToken);

        return Command::SUCCESS;
    }

    /**
     * Retrieve a User instance by email address.
     *
     * @return User|null User instance or null if not found
     */
    protected function getUser(): ?User
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $email = $this->argument('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid email address is required.');
            return null;
        }

        $this->user = User::where('email', $email)->first();

        if ($this->user === null) {
            $this->error('No user found with the provided email.');
        }

        return $this->user;
    }
}
