<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserApiTokens extends Command
{
    /**
     * The user instance.
     */
    protected ?User $user = null;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:tokens {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists User API tokens for a user by a given email address.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = $this->getUser();

        if ($user === null) {
            return 1; // Command failed if user not found
        }

        foreach ($user->tokens as $token) {
            $this->warn($token->plainTextToken); // Display each token
        }

        return 0; // Command succeeded
    }

    /**
     * Returns an instance of User by email address.
     *
     * This method caches the user instance after the first successful retrieval.
     */
    protected function getUser(): ?User
    {
        // Return cached user if available
        if ($this->user !== null) {
            return $this->user;
        }

        $email = $this->argument('email');

        if (empty($email)) {
            $this->error('A valid email address is required.');

            return null;
        }

        // Retrieve user by email
        $this->user = User::where('email', $email)->first();

        if ($this->user === null) {
            $this->error('No user found with the provided email address.');

            return null;
        }

        return $this->user;
    }
}
