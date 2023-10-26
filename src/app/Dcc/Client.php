<?php

namespace App\Dcc;

use Illuminate\Console\Command;

use React\EventLoop\Loop;

use App\Models\Nick,
    App\Models\Network;

class Client
{
    /**
     * Nick selected for run
     *
     * @var Nick
     */
    protected $nick;

    /**
     * network selected for run
     *
     * @var Network
     */
    protected $network;

    /**
     * Console for using this client
     *
     * @var Command
     */
    protected $console;

    public function __construct(Nick $nick, Network $network, Command $console) {
        $this->nick = $nick;
        $this->network = $network;
        $this->console = $console;
    }

    public function open(string $host, string $downloadUri = null)
    {
        if (null !== $downloadUri) {
            $port = $this->findAvailablePort();
            $stream = stream_socket_server("tcp://$host:$port");
            stream_set_blocking($stream, false);
            $loop = Loop::get();

            $loop->addReadStream($stream, function ($stream) use ($downloadUri) {
                $file = fopen($downloadUri, 'a')
                    or die("Unable to open download uri: {$downloadUri}!");

                // Read the stream to the file in 1k chunks.
                while (!feof($stream)) {
                    $chunk = fread($stream, 1024);

                    if ($chunk === '') {
                        $this->console->line('[END]' . PHP_EOL);
                        Loop::removeReadStream($stream);
                        fclose($stream);
                        fclose($file);
                        return;
                    }

                    fwrite($file, $chunk);
                }

                fclose($file);
            });

            $loop->addPeriodicTimer(5, function () {
                $memory = memory_get_usage() / 1024;
                $formatted = number_format($memory, 3).'K';
                $this->console->line("Current memory usage: {$formatted}");
            });

            $loop->run();
        }
    }

    /**
     * Attempts to find an available port.
     */
    private function findAvailablePort(): int
    {
        $port = null;
        $address = 'localhost';
        $sock = socket_create(AF_INET, SOCK_STREAM, 0);
        socket_bind($sock, $address, 0) or die('Could Not Bind Socket');
        socket_getsockname($sock, $address, $port);
        socket_close($sock);
        return $port;
    }
}