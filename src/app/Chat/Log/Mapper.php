<?php

namespace App\Chat\Log;

use App\Exceptions\UnmappedChatLogEventException;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('LOG_EX')) {
    define('LOG_EX', '.log');
}

class Mapper
{
    const INSTANCE_FOLDER = 'instances';

    /**
     * Logs
     */
    const LOG_NOTICE = 'notice';
    const LOG_CONSOLE = 'console';
    const LOG_MESSAGE = 'message';
    const LOG_PRIVMSG = 'privmsg';
    const LOG_EVENT = 'event';

    /**
     * Network events
     */
    const EVENT_CLOSE = 'close';
    const EVENT_CONSOLE = 'console';
    const EVENT_MOTD = 'motd';
    const EVENT_PING = 'ping';
    const EVENT_REGISTERED = 'registered';
    const EVENT_VERSION = 'version';

    const EVENT_CTCP = 'ctcp';
    const EVENT_DCC = 'dcc';
    const EVENT_INVITE = 'invite';
    const EVENT_NICK = 'nick';
    const EVENT_NOTICE = 'notice';
    const EVENT_QUIT = 'quit';

    const EVENT_PRIVMSG = 'privmsg';

    /**
     * Channel events
     */
    const EVENT_JOIN = 'joinInfo';
    const EVENT_TOPIC = 'topic';
    const EVENT_KICK = 'kick';
    const EVENT_PART = 'part';
    const EVENT_MODE = 'mode';

    const EVENT_MESSAGE = 'message';

    const CONSOLE_LOG_EVENT = [
        self::EVENT_CLOSE,
        self::EVENT_CONSOLE,
        self::EVENT_MOTD,
        self::EVENT_PING,
        self::EVENT_REGISTERED,
        self::EVENT_VERSION,
    ];

    const NOTICE_LOG_EVENT = [
        self::EVENT_CTCP,
        self::EVENT_DCC,
        self::EVENT_INVITE,
        self::EVENT_NICK,
        self::EVENT_NOTICE,
        self::EVENT_QUIT,
    ];

    const EVENT_LOG_EVENT = [
        self::EVENT_JOIN,
        self::EVENT_TOPIC,
        self::EVENT_KICK,
        self::EVENT_PART,
        self::EVENT_MODE,
    ];

    /**
     * Root of where the logging directories are stored.
     *
     * @var string
     */
    protected $logRoot;

    /**
     * Base Uri of where the logging for this instance will be.
     *
     * @var string
     */
    protected $instanceUri;

    /**
     * Name of the network for this log mapping instance.
     *
     * @var string
     */
    protected $networkName;

    /**
     * Nick for this log mapping instance.
     *
     * @var string
     */
    protected $nick = [];

    /**
     * List of channels for this log mapping instance.
     *
     * @var array
     */
    protected array $channels = [];

    /**
     * Associative array for mapping an event to a log;
     *
     * @var array
     */
    protected array $map = [];

    public function __construct(string $logRoot, string $networkName, string $nick, array $channels = [])
    {
        $this->logRoot = $networkName;
        $this->networkName = $networkName;
        $this->nick = $nick;
        $this->channels = $channels;
        $this->instanceUri = $logRoot . DS . self::INSTANCE_FOLDER . DS . $this->nick . DS . $this->networkName;
    }

    /**
     * Gets the Uri of a log with given event.
     *
     * @param string $event
     * @return string
     */
    public function getLog(string $event): string
    {
        $map = $this->getMap();
        if (!isset($map[$event])) {
            throw new UnmappedChatLogEventException("Unable to log event: $event. No log is mapped for this event.");
        }

        return $map[$event];
    }

    /**
     * Returns mapping to logs for all of the chat events.
     */
    public function getMap($reset = false): array
    {
        if ($reset || 0 === count($this->map)) {
            $map = [];
            $consoleLog = $this->getLogUri(self::LOG_CONSOLE);
            $noticeLog = $this->getLogUri(self::LOG_NOTICE);
            $privMsgLog = $this->getLogUri(self::LOG_PRIVMSG);

            $map[self::EVENT_PRIVMSG] = $privMsgLog;

            foreach(self::CONSOLE_LOG_EVENT as $event) {
                $map[$event] = $consoleLog;
            }

            foreach(self::NOTICE_LOG_EVENT as $event) {
                $map[$event] = $noticeLog;
            }

            foreach($this->channels as $channel) {
                $map = array_merge($map, $this->mapChannel($channel));
            }

            $this->map = $map;
        }

        return $this->map;
    }

    /**
     * Maps an individual channel.
     *
     * @param string $channel
     * @return array
     */
    public function mapChannel(string $channel): array
    {
        $map = [];
        $eventLog = $this->getLogUri(self::LOG_EVENT, $channel);
        $messageLog = $this->getLogUri(self::LOG_MESSAGE, $channel);

        foreach(self::EVENT_LOG_EVENT as $event) {
            // Composite key for event+name: topic#mg-chat
            $map[$event . $channel] = $eventLog;
        }

        $map[self::EVENT_MESSAGE . $channel] = $messageLog;


        return $map;
    }

    /**
     * Formulates the uri of a log based on the input.
     *
     * @var string $name
     * @var string $channel
     * @return string
     */
    public function getLogUri(string $name, ?string $channel = null): string
    {
        $uri = $this->instanceUri;

        if (null !== $channel) {
            $uri .= DS . $channel;
        }

        return  $uri .= DS . $name . LOG_EX;
    }

    /**
     * Event string for a direct message
     *
     * @param string $nick
     * @return string
     */
    public static function directMessageEvent(string $nick): string
    {
        return self::EVENT_MESSAGE . $nick;
    }


    /**
     * Get list of channels for this log mapping instance.
     *
     * @return  array
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * Add a new channel to the list of channels.
     *
     * @param string $channel
     * @return void
     */
    public function addChannel(string $channel): void
    {
        $this->channels[] = $channel;
        // Update map since channels changed.
        $this->getMap(true);
    }

    /**
     * Set list of channels for this log mapping instance.
     *
     * @param  array  $channels  List of channels for this log mapping instance.
     *
     * @return  self
     */
    public function setChannels(array $channels): void
    {
        $this->channels = $channels;
        // Update map since channels changed.
        $this->getMap(true);
    }

    /**
     * Get base Uri of where the logging for this instance will be.
     *
     * @return  string
     */
    public function getInstanceUri(): string
    {
        return $this->instanceUri;
    }

    /**
     * Set base Uri of where the logging for this instance will be.
     *
     * @param  string  $instanceUri  Base Uri of where the logging for this instance will be.
     *
     * @return  void
     */
    public function setInstanceUri(string $instanceUri): void
    {
        $this->instanceUri = $instanceUri;
    }

    /**
     * Get name of the network for this log mapping instance.
     *
     * @return  string
     */
    public function getNetworkName(): string
    {
        return $this->networkName;
    }
}
