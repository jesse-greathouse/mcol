<?php

namespace App\Chat\Log;

use App\Exceptions\DirectoryCreateFailedException,
    App\FileSystem,
    App\Packet\Parse;

class Diverter
{
    use FileSystem;

    /**
     * Instance of mapping instructions for logs.
     *
     * @var Mapper $mapper
     */
    protected $mapper;

    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
        $this->refreshLogs();
    }

    /**
     * Logs a message to a log based on an event and optionally a channel.
     *
     * @param string $event
     * @param string $message
     * @param ?string $channel
     * @return void
     */
    public function log(string $event, string $message, ?string $channel = null): void
    {
        if (null !== $channel) {
            $event .= $channel;
        }

        $uri = $this->mapper->getLog($event);
        $this->logSanityCheck($uri);

        $clean  = Parse::cleanMessage($message);
        $message = '[' . date("c", strtotime('now')) . "] $clean \n";
        $fh = fopen($uri, 'a');
        fwrite($fh, $message);
        fclose($fh);
    }

    /**
     * Deletes all old logs and instantiates them again.
     * Logs must be ready to stream even before they are written to.
     *
     * @return void
     */
    public function refreshLogs(): void
    {
        foreach($this->mapper->getMap() as $log) {
            if (file_exists($log)) {
                unlink($log);
            }

            $this->logSanityCheck($log);
        }
    }

    /**
     * Deletes all old channel logs and instantiates them again.
     * Logs must be ready to stream even before they are written to.
     *
     * @param string $chanel
     * @return void
     */
    public function refreshChannelLogs(string $channel)
    {
        $events = array_merge(Mapper::EVENT_LOG_EVENT, [Mapper::EVENT_MESSAGE]);
        forEach($events as $event) {
            $channelEvent = $event . $channel;
            $log = $this->mapper->getLog($channelEvent);
            if (file_exists($log)) {
                unlink($log);
            }

            $this->logSanityCheck($log);
        }
    }

    /**
     * Makes sure a log exists before we start writing to it.
     *
     * @param string $uri
     * @return void
     */
    public function logSanityCheck(string $uri): void
    {
        if (file_exists($uri)) return;

        ['dirname' => $dirName] = pathinfo($uri);

        if (!$this->preparePath($dirName)) {
            throw new DirectoryCreateFailedException("The log path: \"$uri\" could not be created.");
        }

        touch($uri);
    }

    /**
     * Add a new channel to the Mapper.
     *
     * @param string $channel
     * @return void
     */
    public function addChannel(string $channel): void
    {
        $this->mapper->addChannel($channel);
        $this->refreshChannelLogs($channel);
    }

    /**
     * Get base Uri of where the logging diverter sends logs.
     *
     * @return  string
     */
    public function getInstanceUri(): string
    {
        return $this->mapper->getInstanceUri();
    }
}
