<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\User;

class GenerateApiToken extends Command
{
    /**
     * User Selected
     *
     * @var User
     */
    protected $user;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:generate-api-token {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates an Api token for a user by a given email address.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = $this->getUser();

        if (null === $user) {
            return 1;
        }

        $token = $user->createToken('bearer_token');
        $this->warn($token->plainTextToken);
    }

    /**
     * Returns an instance of User by email address.
     *
     * @return User|null
     */
    protected function getUser(): User|null
    {
        if (null === $this->user) {
            $email = $this->argument('email');

            if (null === $email) {
                $this->error('A valid email address is required.');
                return null;
            }

            $user = User::where('email', $email)->first();

            if (null === $user) {
                $this->error('A valid email is required.');
                return null;
            }
            $this->user = $user;
        }

        return $this->user;
    }
}