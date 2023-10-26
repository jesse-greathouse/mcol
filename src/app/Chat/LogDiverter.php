<?php

namespace App\Chat;

use App\Chat\PacketLocator;

class LogDiverter
{

    /**
     * Client Instance Log Uri: var/instances/nick/network.log
     * 
     * @var string
     * 
     */
    protected $instanceLogUri;

    public function __construct(string $instanceLogUri)
    {
        $this->instanceLogUri = $instanceLogUri;
    }

    public function log(string $message): void
    {
        $clean  = PacketLocator::cleanMessage($message);
        $message = '[' . date("c", strtotime('now')) . "] $clean \n";
        $fh = fopen($this->instanceLogUri, 'a');
        fwrite($fh, $message);
        fclose($fh);
    }
}
