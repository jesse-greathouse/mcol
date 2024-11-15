<?php

namespace App\Chat\Log;

use App\Exceptions\ChatLogStreamException,
    App\Exceptions\IllegalChatLogMapperInstance;

use \Generator;

class Streamer
{
    const CHUNK_LENGTH = 1024;
    const DEFAULT_MAX_BUFFER = 1e+7; // 10 MB

    const MAX_BUFFER = [
        Mapper::LOG_CONSOLE => self::DEFAULT_MAX_BUFFER,
        Mapper::LOG_NOTICE  => self::DEFAULT_MAX_BUFFER,
        Mapper::LOG_PRIVMSG => self::DEFAULT_MAX_BUFFER,
        Mapper::LOG_EVENT   => self::DEFAULT_MAX_BUFFER,
        Mapper::LOG_MESSAGE => self::DEFAULT_MAX_BUFFER,
    ];

    /**
     * Array of mappers.
     *
     * @var array <string,[Mapper]>
     */
    protected array $mappers = [];

    public function __construct(array $mappers)
    {
        $this->mappers = $mappers;
    }

    /**
     * Streams a console log.
     *
     * @param string $networkName
     * @param int $offset
     * @return Generator
     */
    public function console(string $networkName, int $offset = 0): Generator
    {
        return $this->streamLog(Mapper::LOG_CONSOLE, $networkName, $offset);
    }

    /**
     * Streams a notice log.
     *
     * @param string $networkName
     * @param int $offset
     * @return Generator
     */
    public function notice(string $networkName, int $offset = 0): Generator
    {
        return $this->streamLog(Mapper::LOG_NOTICE, $networkName, $offset);
    }

    /**
     * Streams a privmsg log.
     *
     * @param string $networkName
     * @param int $offset
     * @return Generator
     */
    public function privmsg(string $networkName, int $offset = 0): Generator
    {
        return $this->streamLog(Mapper::LOG_PRIVMSG, $networkName, $offset);
    }

    /**
     * Streams an event log.
     *
     * @param string $networkName
     * @param string $channelName
     * @param int $offset
     * @return Generator
     */
    public function event(string $networkName, string $channelName, int $offset = 0): Generator
    {
        return $this->streamLog(Mapper::LOG_EVENT, $networkName, $offset, $channelName);
    }

    /**
     * Streams an message log.
     *
     * @param string $networkName
     * @param string $channelName
     * @param int $offset
     * @return Generator
     */
    public function message(string $networkName, string $channelName, int $offset = 0): Generator
    {
        return $this->streamLog(Mapper::LOG_MESSAGE, $networkName, $offset, $channelName);
    }

    /**
     * Streams a log file.
     *
     * @param string $logName
     * @param string $networkName
     * @param int $offset
     * @param string|null $channelName
     * @return Generator
     */
    public function streamLog(string $logName, string $networkName, int $offset = 0, string $channelName = null): Generator
    {
        $mapper = $this->getMapper($networkName);
        $log = $mapper->getLogUri($logName, $channelName);
        $offset = $this->sanitizeOffset($log, self::MAX_BUFFER[$logName], $offset);

        try {
            $fh = fopen($log,'r');
            fseek($fh, $offset);

            while (($buffer = fgets($fh, self::CHUNK_LENGTH)) !== false) {
                $bytes = strlen($buffer);
                $offset += $bytes;
                yield $buffer;
            }

            // Add meta/offset.
            // Meta/Offset helps the client know where to start streaming on the next request.
            yield '[meta]: ' . json_encode(['offset' => $offset]);

            fclose($fh);
            unset($fh);
        } catch(\Exception $e) {
            throw new ChatLogStreamException("Unable to stream $networkName chat log: \"$log\"\n " . $e->getMessage());
        }
    }

    /**
     * Sanity check for offset, makes sure it doesn't read too much of the file.
     * File size - max buffer = offset.
     *
     * @param string $uri
     * @param int $max
     * @param int $offset
     * @return int
     */
    public function sanitizeOffset(string $uri, int $max, int $offset = null): int
    {
        clearstatcache(true, $uri); // clears the caching of filesize
        $fileSize = fileSize($uri);
        $delta = $fileSize - $max;

        // If offset is greater than the filesize, it probably means that the logs were reset.
        // Set the offset to zero.
        if ($offset > $fileSize) {
            $offset = 0;
        }

        if ($delta > $offset) {
            return $delta;
        }

        return $offset;
    }


    /**
     * Returns a mapper by the name of the network.
     *
     * @param string $networkName
     * @return Mapper
     */
    public function getMapper(string $networkName): Mapper
    {
        if (!isset($this->mappers[$networkName])) {
            throw new IllegalChatLogMapperInstance("Mapper for: $networkName was not found.");
        }

        return $this->mappers[$networkName];
    }

    /**
     * Adds a mapper to the collection of mappers in this stance.
     *
     * @param Mapper $mapper
     * return void
     */
    public function addMapper(Mapper $mapper): void
    {
        $this->mappers[$mapper->getNetworkName()] = $mapper;
    }

    /**
     * Get <string,[Mapper]>
     *
     * @return  array
     */
    public function getMappers(): array
    {
        return $this->mappers;
    }
}
