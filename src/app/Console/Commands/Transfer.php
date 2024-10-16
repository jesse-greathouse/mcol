<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Media\TransferManager;

use \Exception;

class Transfer extends Command
{
    /**
     *
     * Uri of file to be transferred.
     *
     * @var String
     */
    protected $uri;

    /**
     *
     * Path of where the completed transfer will be.
     *
     * @var String
     */
    protected $destination;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:transfer {uri} {destination}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer a file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $uri = $this->getUri();
        $destination = $this->getDestination();
        $varDir = env('VAR', '/var/mcol');
        $tmpDir = "$varDir/transfer";
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir);
        }
        $options = ['tmp_dir' => $tmpDir];

        $manager = new TransferManager($uri, $destination, $options);
        $manager->transfer();
    }


    /**
     * Gets and validates the uri as input.
     *
     * @return string
     */
    public function getUri(): string
    {
        if (null === $this->uri) {
            $this->uri = $this->argument('uri');
            if (null === $this->uri || '' === $this->uri) {
                throw new Exception("Invalid file uri.");
            }
        }

        return $this->uri;
    }

    /**
     * Gets and validates the destination path as input.
     *
     * @return string
     */
    public function getDestination(): string
    {
        if (null === $this->destination) {
            $this->destination = $this->argument('destination');
            if (null === $this->destination || '' === $this->destination) {
                throw new \Exception("Invalid destination path.");
            }
        }

        return $this->destination;
    }
}
