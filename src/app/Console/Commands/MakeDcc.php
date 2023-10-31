<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Dcc\Client;

class MakeDcc extends Command
{
    /**
     * Host selected for connection
     *
     * @var string
     */
    protected $host;

    /**
     * network selected for run
     *
     * @var string
     */
    protected $port;

    /**
     * Name of file to be sent.
     *
     * @var string
     */
    protected $file;

    /**
     * Name of file size of the file.
     *
     * @var string
     */
    protected $fileSize;

    /**
     * The name of the file.
     *
     * @var string
     */
    protected $signature = 'mcol:make-dcc {--host=} {--port=} {--file=} {--file-size=} {--resume=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Direct Client Connection via stream socket';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $host = $this->getHost();
        if (!$host) $this->error('A valid --host is required.');
        
        $port = $this->getPort();
        if (!$port) $this->error('A valid --port is required.');

        $file = $this->getFile();
        if (!$file) $this->error('A valid --file is required.');

        $fileSize = $this->getFileSize();

        $resume = ($this->option('resume')) ? true : false;
        
        if (!$host || !$port || !$file) return;

        $dcc = new Client($this);
        $dcc->open(long2ip($host), $port, $file, $fileSize);
    }

    /**
     * Returns a string of the host name.
     *
     * @return string|null
     */
    protected function getHost(): string|null
    {
        if (null === $this->host) {
            $host = $this->option('host');

            if (null === $host) {
                $this->error('A valid --host is required.');
            }

            $this->host  = $host;
        }

        return $this->host;
    }

    /**
     * Returns a string of the socket port.
     *
     * @return string|null
     */
    protected function getPort(): string|null
    {
        if (null === $this->port) {
            $port = $this->option('port');

            if (null === $port) {
                $this->error('A valid --port is required.');
            }

            $this->port = $port;
        }

        return $this->port;
    }

    /**
     * Returns a string of the file name.
     *
     * @return string|null
     */
    protected function getFile(): string|null
    {
        if (null === $this->file) {
            $file = $this->option('file');

            if (null === $file) {
                $this->error('A valid --file is required.');
            }

            $this->file = $file;
        }

        return $this->file;
    }

    /**
     * Returns a size of the file.
     *
     * @return int|null
     */
    protected function getFileSize(): int|null
    {
        if (null === $this->fileSize) {
            $fileSize = (integer)$this->option('file-size');

            $this->fileSize = $fileSize;
        }

        return $this->fileSize;
    }
}
