<?php

namespace App\Chat;

use Illuminate\Console\Command,
    Illuminate\Contracts\Cache\Repository,
    Illuminate\Database\QueryException;

use JesseGreathouse\PhpIrcClient\IrcClient,
    JesseGreathouse\PhpIrcClient\IrcChannel,
    JesseGreathouse\PhpIrcClient\IrcClientEvent,
    JesseGreathouse\PhpIrcClient\Exceptions\ParseChannelNameException,
    JesseGreathouse\PhpIrcClient\Options\ClientOptions;

use App\Chat\Log\Diverter as LogDiverter,
    App\Chat\Log\Mapper as LogMapper,
    App\Events\HotReportLine as HotReportLineEvent,
    App\Events\HotReportSummary as HotReportSummaryEvent,
    App\Events\PacketSearchResult as PacketSearchResultEvent,
    App\Events\PacketSearchSummary as PacketSearchSummaryEvent,
    App\Exceptions\NetworkWithNoChannelException,
    App\Exceptions\UnmappedChatLogEventException,
    App\Jobs\CheckFileDownloadCompleted,
    App\Jobs\DccDownload,
    App\Models\Bot,
    App\Models\Channel,
    App\Models\Client as ClientModel,
    App\Models\Download,
    App\Models\FileDownloadLock,
    App\Models\HotReport,
    App\Models\HotReportLine,
    App\Models\Instance,
    App\Models\Nick,
    App\Models\Network,
    App\Models\Packet,
    App\Models\PacketSearch,
    App\Models\PacketSearchResult,
    App\Packet\MediaType\MediaTypeGuesser,
    App\Packet\Parse;

use Illuminate\Database\Eloquent\Collection;

use DateTime,
    TypeError;

class Client
{
    const VERSION = 'Mcol-[alpha-build] -- The Media Collector (https://github.com/jesse-greathouse/mcol)';
    const SUPPORTED_ENCODING = 'UTF-8';

    const LINE_COLUMN_SPACES = 50;
    const QUEUED_MASK = '/^Queued \d+h\d+m for \"(.+)\", in position (\d+) of (\d+)\. .+$/';
    const QUEUED_RESPONSE_MASK = '/pack ([0-9]+) \(\"(.+)\"\) in position ([0-9]+)\./';
    const REQUEST_INSTRUCTIONS_MASK = '/\|10\s(.*)04\s\|10\s(.*)04\s\|09\s\/msg\s(.*)\sXDCC\sSEND\s([0-9].*)\s04.*/';
    const HOT_REPORT_RESULT = '/(\d\.\d)\s0\d\s([A-Za-z0-9_\.\-]+)\s+(\d\.\d)\s\d\s([A-Za-z0-9_\.\-]+)/';
    const SEARCH_SUMMARY_MASK = '/(\#[A-Za-z].*)\s\-\sFound\s([0-9].*)\sONLINE Packs/';
    const HOT_REPORT_SUMMARY_MASK = '/\d\d(\#[A-Za-z0-9]+)\s+(.*)$/';

    /**
     * Client representation in database.
     *
     * @var ClientModel
     */
    protected $clientModel;

    /**
     * A lookup table of instantiated Channel Models associated with this client.
     * Keeps instantiated channels in memory so we don't have to keep hitting the DB.
     *
     * @var array<string, Channel>
     */
    protected array $channels = [];

    /**
     * A lookup table of instantiated Bot Models associated with this client.
     * Keeps instantiated bots in memory so we don't have to keep hitting the DB.
     *
     * @var array<string, Bot>
     */
    protected array $bots = [];

    /**
     * Stores the botID => Channel map so that lookups dont have to happen more than once.
     *
     * @var array<int, Channel>
     */
    protected array $botChannelMap = [];

    /**
     * The server host name to which the client is connected.
     *
     * @var string
     */
    protected $connectedServer;

    /**
     * The masked hostname of the client on the network.
     *
     * @var string
     */
    protected $hostMask;

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
     * Application cache.
     *
     * @var Repository
     */
    protected $cache;

    /**
     * Console for using this client
     *
     * @var Command
     */
    protected $console;

    /**
     * Instance Associated with this client.
     *
     * @var Instance
     */
    protected $instance;

    /**
     * DownloadProgressManager instance for this client.
     *
     * @var DownloadProgressManager
     */
    protected $downloadProgressManager;

    /**
     * LogDiverter instance for this client.
     *
     * @var LogDiverter
     */
    protected $logDiverter;

    /**
     * OperationManager instance for this client.
     *
     * @var OperationManager
     */
    protected $operationManager;

    /**
     * IRC client
     *
     * @var IrcClient
     */
    protected $client;

    public function __construct(Nick $nick, Network $network, Repository $cache, Command $console) {
        $logRoot = env('LOG_DIR', '/var/log');
        $this->cache = $cache;
        $this->nick = $nick;
        $this->network = $network;
        $this->console = $console;
        $this->logDiverter = new LogDiverter(new LogMapper($logRoot, $network->name, $nick->nick));

        $options = new ClientOptions($nick->nick);
        $this->client = new IrcClient("{$network->firstServer->host}:6667", $options, self::VERSION);

        $this->clientModel = ClientModel::updateOrCreate(
            [ 'network_id' => $this->network->id, 'nick_id' => $this->nick->id ],
            [ 'enabled' => true, 'meta' => $this->client->toJson() ],
        );

        $this->assignHandlers();
    }

    /**
     * Assigns all handlers for the various events.
     *
     * @return void
     */
    protected function assignHandlers(): void
    {
        $this->versionHandler();
        $this->noticeHandler();
        $this->joinHandler();
        $this->registeredHandler();
        $this->pingHandler();
        $this->nickHandler();
        $this->messageHandler();
        $this->modeHandler();
        $this->motdHandler();
        $this->privMessageHandler();
        $this->ctcpHandler();
        $this->dccHandler();
        $this->kickHandler();
        $this->quitHandler();
        $this->topicHandler();
        $this->partHandler();
        $this->consoleHandler();
        $this->inviteHandler();
        $this->namesHandler();

        // disconnectHandler is moved to register instance.
        // The TCP connection will not be established until it's opened.
        // The TCP connection context is necessary for the disconnect handler.
        // $this->disconnectHandler();
    }

    /**
     * Registers a new instance and initializes managers.
     *
     * This method retrieves necessary metadata, updates or creates an instance record,
     * initializes the operation and download progress managers, and sets up the disconnect handler.
     *
     * @return void
     */
    protected function registerInstance(): void
    {
        $logUri = $this->logDiverter->getInstanceUri();
        $pid = getmypid() ?: null;

        // Retrieve connection status from the client metadata
        $meta = $this->client->toArray();
        $isConnected = $meta['connection']['is_connected'] ?? false;

        // Update or create an instance entry
        $this->instance = Instance::updateOrCreate(
            ['client_id' => $this->clientModel->id],
            ['is_connected' => $isConnected, 'log_uri' => $logUri, 'pid' => $pid]
        );

        // Initialize operation and download progress managers
        $this->operationManager = new OperationManager($this->client, $this->instance, $this->console);
        $this->downloadProgressManager = new DownloadProgressManager($this->instance, $this->console);

        // Set up disconnect handler
        $this->disconnectHandler();
    }

    public function pingHandler(): void
    {
        $this->client->on(IrcClientEvent::PING, function (string $pinger) {
            try {
                $convertedIp = long2ip($pinger);
                if ($convertedIp !== false) {
                    $pinger = $convertedIp;
                    $this->console->info("Pinged from IP: '{$pinger}'");
                }
            } catch (TypeError) {
                $this->console->info("Pinged by: '{$pinger}'");
            }

            $pingMessage = "{$pinger} PING -> {$this->nick->nick}";
            $pongMessage = "{$this->nick->nick} PONG -> {$pinger}";

            $this->console->info($pingMessage);
            $this->console->info($pongMessage);
            $this->logDiverter->log(LogMapper::EVENT_PING, $pingMessage);
            $this->logDiverter->log(LogMapper::EVENT_PING, $pongMessage);
        });
    }

    /**
     * Handles a join event by logging user joins and handling potential exceptions.
     *
     * @return void
     */
    public function joinHandler(): void
    {
        $this->client->on(IrcClientEvent::JOIN, function (string $user, string $channelName) {
            $message = "$user joined $channelName";
            $this->console->info($message);

            try {
                $this->logDiverter->log(LogMapper::EVENT_JOIN, $message, $channelName);
            } catch (UnmappedChatLogEventException) {
                $this->console->error(
                    "Unmapped joinInfo event for channel: \"$channelName\".
                    (This usually happens due to a truncated UDP packet.)"
                );
            }
        });
    }

    /**
     * Handles a kick event by logging the event and updating the channel state.
     *
     * @return void
     */
    public function kickHandler(): void
    {
        $this->client->on(IrcClientEvent::KICK, function ($channel, string $user, string $kicker, string $reason) {
            $channel = $this->updateChannel($channel);
            $channelName = $channel->getName();

            $message = "$user was kicked from $channelName by $kicker. Reason: $reason";
            $this->console->error($message);
            $this->logDiverter->log(LogMapper::EVENT_KICK, $message, $channelName);
        });
    }

    /**
     * Handles a nickname change event by logging the change.
     *
     * @return void
     */
    public function nickHandler(): void
    {
        $this->client->on(IrcClientEvent::NICK, function (string $nick, string $newNick) {
            $message = "$nick sets nick: $newNick";
            $this->console->warn($message);
            $this->logDiverter->log(LogMapper::EVENT_NICK, $message);
        });
    }

    /**
     * Handles a quit event and logs the reason for the quit.
     *
     * @return void
     */
    public function quitHandler(): void
    {
        $this->client->on(IrcClientEvent::QUIT, function (string $user, string $reason) {
            $quitPrefix = 'Quit: ';
            $quitMessage = strpos($reason, $quitPrefix) === false ? $reason : substr($reason, strlen($quitPrefix));

            $message = "$user $quitMessage";

            $this->console->warn($message);
            $this->logDiverter->log(LogMapper::EVENT_QUIT, $message);
        });
    }

    /**
     * Handles a part event when a user leaves a channel.
     *
     * @return void
     */
    public function partHandler(): void
    {
        $this->client->on(IrcClientEvent::PART, function (string $user, $channel, string $reason) {
            $channel = $this->updateChannel($channel);
            $channelName = $channel->getName();
            $message = "$user parted: $reason";

            // Log and warn about the user parting the channel
            $this->console->warn("$user parted $channelName: $reason");
            $this->logDiverter->log(LogMapper::EVENT_PART, $message, $channelName);
        });
    }

    /**
     * Handles a mode change event when a user changes a channel's mode.
     *
     * @return void
     */
    public function modeHandler(): void
    {
        $this->client->on(IrcClientEvent::MODE, function ($channel, string $user, string $mode) {
            // Initialize channel name, defaulting to an empty string if not provided.
            $channelName = '';

            if ($channel !== null && $channel !== '') {
                $channel = $this->updateChannel($channel);
                $channelName = $channel->getName();
            }

            // Prepare and trim the mode message.
            $message = trim("$user mode: $mode");

            // Log and warn about the mode change.
            $this->console->warn("$channelName set $message");

            // Log the event based on whether the channel name is available.
            if ($channelName === '') {
                $this->logDiverter->log(LogMapper::EVENT_NOTICE, $message);
            } else {
                $this->logDiverter->log(LogMapper::EVENT_MODE, $message, $channelName);
            }
        });
    }

    /**
     * Handles an invite event when a user invites another user to a channel.
     *
     * @return void
     */
    public function inviteHandler(): void
    {
        $this->client->on(IrcClientEvent::INVITE, function ($channel, string $user) {
            // Retrieve channel name from the channel object.
            $channelName = $channel->getName();

            // Construct the invite message with the user and channel details.
            $message = "$user has invited {$this->nick->nick} to join channel: $channelName";

            // Log the invite event as a warning.
            $this->console->warn("========[ $message");

            // Record the event in the log with the associated channel name.
            $this->logDiverter->log(LogMapper::EVENT_INVITE, $message, $channelName);
        });
    }

    /**
     * Handles a topic change event and logs the change.
     *
     * @return void
     */
    public function topicHandler(): void
    {
        $this->client->on(IrcClientEvent::TOPIC, function ($channel, string $topic) {
            // Retrieve and update the channel information.
            $channel = $this->updateChannel($channel);
            $channelName = $channel->getName();

            // Log the topic change with proper formatting.
            $this->console->info('');
            $this->console->info("                ================[  $channelName Topic ]================");
            $this->console->info($topic);
            $this->console->info('');

            // Record the topic change in the log with the associated channel.
            $this->logDiverter->log(LogMapper::EVENT_TOPIC, $topic, $channelName);
        });
    }

    /**
     * Handles a DCC (Direct Client-to-Client) event and logs the event information.
     *
     * @return void
     */
    public function dccHandler(): void
    {
        $this->client->on(IrcClientEvent::DCC, function ($action, $fileName, $ip, $port, $fileSize = 0) {
            // Construct the message to be logged and displayed.
            $message = 'A DCC event has been sent, with the following information';
            $information = "Action: $action, File Name: $fileName, IP: $ip, Port: $port, File Size: $fileSize";

            // Log the warning message with event information.
            $this->console->warn("$message:");
            $this->console->warn($information);

            // Log the DCC event with detailed information.
            $this->logDiverter->log(LogMapper::EVENT_DCC, "$message: $information");
        });
    }

    /**
     * Handles the registered event (connected) and processes the server registration.
     *
     * @return void
     */
    public function registeredHandler(): void
    {
        $this->client->on(IrcClientEvent::REGISTERED, function (string $server, string $user, string $message, string $hostMask) {
            // Assign the connected server and host mask.
            $this->connectedServer = $server;
            $this->hostMask = $hostMask;

            // Log the connection notice and related information.
            $notice = "$user connected to: $server";
            $this->console->warn($notice);
            $this->logDiverter->log(LogMapper::EVENT_REGISTERED, $notice);

            // Log the registration message.
            $this->console->info($message);
            $this->logDiverter->log(LogMapper::EVENT_REGISTERED, $message);

            // Register the instance.
            $this->registerInstance();

            // Retrieve all parent channels for the network and join them.
            $channels = $this->getParentChannelsForNetwork($this->network);
            foreach ($channels as $channel) {
                $this->logDiverter->addChannel($channel->name);
                $this->client->join($channel->name);
                $this->updateChannel($channel->name, false);

                // Join child channels as well.
                foreach ($channel->children as $child) {
                    $this->logDiverter->addChannel($child->name);
                    $this->client->join($child->name);
                    $this->updateChannel($child->name, false);
                }
            }

            // Update the client after processing all channels.
            $this->updateClient();
        });
    }

    /**
     * Handles the event when the client disconnects, including both intentional and
     * unintentional disconnections.
     *
     * @return void
     */
    public function disconnectHandler(): void
    {
        // Retrieve the client and TCP connections.
        $clientConnection = $this->client->getConnection();
        $tcpConnection = $clientConnection->getConnection();

        // Handle intentional close of the connection.
        $this->client->on(IrcClientEvent::CLOSE, function () {
            $this->terminateInstance();
        });

        // Handle disconnect not initiated by the client.
        $tcpConnection->on(IrcClientEvent::CLOSE, function () use ($clientConnection) {
            $clientConnection->setConnected(false);
            $this->terminateInstance();
        });

        // Handle the 'end' event of the TCP connection.
        $tcpConnection->on(IrcClientEvent::END, function () use ($clientConnection) {
            $clientConnection->setConnected(false);
            $this->terminateInstance();
        });
    }

    /**
     * Handles the 'version' event, logging and warning the client version.
     *
     * @return void
     */
    public function versionHandler(): void
    {
        // Handle the version event and log the version.
        $this->client->on(IrcClientEvent::VERSION, function () {
            $message = 'VERSION ' . $this->client->getVersion();
            $this->console->warn($message);
            $this->logDiverter->log(LogMapper::EVENT_VERSION, $message);
        });
    }

    /**
     * Handles CTCP messages, logging the action, command, and parameters.
     *
     * @return void
     */
    public function ctcpHandler(): void
    {
        // Handle the CTCP event and log the details.
        $this->client->on(IrcClientEvent::CTCP, function (string $action, array $args, string $command) {
            $message = "CTCP: $command | action: $action params: " . json_encode($args);
            $this->console->warn($message);
            $this->logDiverter->log(LogMapper::EVENT_CTCP, $message);
        });
    }

    /**
     * Handles Private Messages
     *
     * @return void
     */
    public function privMessageHandler(): void
    {
        $this->client->on(IrcClientEvent::PRIVMSG, function (string $userName, $target, string $message) {

            // If the Message string is empty don't bother parsing, just warn in the console.
            if (strlen($message) < 1) {
                $this->console->warn("Empty message from $userName to $target");
                return;
            }

            $this->logDiverter->log(LogMapper::LOG_PRIVMSG, "$userName: $message");
            $this->console->warn("$userName to $target says: $message");

            // VERSION handling
            if (strpos($message, 'VERSION') !== false) {
                $this->processVersionRequest($userName, $message);
                return;
            }

            // DCC SEND Protocol
            if (strpos($message, 'DCC SEND') !== false) {
                $this->processDccSend($userName, $message);
                return;
            }

            // DCC ACCEPT Protocol
            if (strpos($message, 'DCC ACCEPT') !== false) {
                $this->processDccAccept($userName, $message);
                return;
            }

            // Unhandled DCC Action
            if (strpos($message, 'DCC') !== false) {
                $this->logUnhandledDccAction($message);
                return;
            }

            return;
        });
    }

    /**
     * Processes the VERSION request.
     *
     * @param string $userName
     * @param string $message
     * @return void
     */
    protected function processVersionRequest(string $userName, string &$message): void
    {
        $message .= ' ' . self::VERSION;
        $this->client->say($userName, self::VERSION);
        $this->console->warn($message);
        $this->logDiverter->log(LogMapper::EVENT_VERSION, $message);
    }

    /**
     * Processes the DCC SEND command.
     *
     * @param string $userName
     * @param string $message
     * @return void
     */
    protected function processDccSend(string $userName, string $message): void
    {
        $params = $this->extractDccParams($message);

        // Check if file exists and process accordingly
        $uri = env('DOWNLOAD_DIR', '/var/download') . "/{$params['fileName']}";
        if (file_exists($uri)) {
            $this->queueDccDownloadJob($userName, $params, $uri);
        } else {
            DccDownload::dispatch($params['ip'], $params['port'], $params['fileName'], $params['fileSize'], $userName)->onQueue('download');
            $this->logAndWarnDccJobQueued($userName, $params);
        }
    }

    /**
     * Processes the DCC ACCEPT command.
     *
     * @param string $userName
     * @param string $message
     * @return void
     */
    protected function processDccAccept(string $userName, string $message): void
    {
        $params = $this->extractDccParams($message);
        $this->console->warn($message);
        $this->logDiverter->log(LogMapper::EVENT_NOTICE, $message);

        $this->logAndWarnDccJobQueued($userName, $params);
        DccDownload::dispatch($params['ip'], $params['port'], $params['fileName'], $params['position'], $userName, $params['position'])->onQueue('download');
    }

    /**
     * Logs and warns about an unhandled DCC action.
     *
     * @param string $message
     * @return void
     */
    protected function logUnhandledDccAction(string $message): void
    {
        $notice = "Unhandled DCC Action: $message";
        $this->console->warn("||||||||||||| ===> $notice");
        $this->logDiverter->log(LogMapper::EVENT_NOTICE, $notice);
    }

    /**
     * Extracts DCC parameters from the message.
     *
     * @param string $message
     * @return array
     */
    protected function extractDccParams(string $message): array
    {
        [, , $fileName, $ip, $port, $fileSizeOrPosition] = explode(' ', $message);

        return [
            'fileName' => $fileName,
            'ip' => $this->cleanNumericStr($ip),
            'port' => $this->cleanNumericStr($port),
            'fileSize' => $this->cleanNumericStr($fileSizeOrPosition),
            'position' => isset($fileSizeOrPosition) ? $this->cleanNumericStr($fileSizeOrPosition) : null
        ];
    }

    /**
     * Queues a DCC download job when the file exists.
     *
     * @param string $userName
     * @param array $params
     * @param string $uri
     * @return void
     */
    protected function queueDccDownloadJob(string $userName, array $params, string $uri): void
    {
        $position = filesize($uri);
        if ($position !== false) {
            $positionCln = $this->cleanNumericStr($position);
            DccDownload::dispatch($params['ip'], $params['port'], $params['fileName'], $positionCln, $userName, $positionCln)->onQueue('download');
            $this->logAndWarnDccJobQueued($userName, $params);
        } else {
            unlink($uri);
        }
    }

    /**
     * Logs and warns that a DCC download job has been queued.
     *
     * @param string $userName
     * @param array $params
     * @return void
     */
    protected function logAndWarnDccJobQueued(string $userName, array $params): void
    {
        $notice = "Queued DCC Download Job: host: {$params['ip']} port: {$params['port']} file: {$params['fileName']} file-size: {$params['fileSize']} bot: '$userName'";
        $this->console->warn($notice);
        $this->logDiverter->log(LogMapper::EVENT_NOTICE, $notice);
    }

    /**
     * Handles standard messages in the channel.
     *
     * @return void
     */
    public function motdHandler(): void
    {
        $this->client->on(IrcClientEvent::MOTD, function (string $message) {
            $cleanMessage = Parse::cleanMessage($message);
            $this->console->warn("[ {$this->network->name} ]: $cleanMessage");
            $this->logDiverter->log(LogMapper::EVENT_PING, $cleanMessage);
        });
    }

    /**
     * Handles standard messages in the channel.
     *
     * @return void
     */
    public function messageHandler(): void
    {
        $directMessageHandle = IrcClientEvent::MESSAGE . $this->nick->nick;

        $this->client->on(IrcClientEvent::MESSAGE, function (string $from, IrcChannel $channel = null, string $message) {
            if ($channel === null) {
                return;
            }

            // Update the Channel Metadata.
            $channel = $this->getChannelFromClient($channel);

            try {
                $this->logDiverter->log(LogMapper::EVENT_MESSAGE, "$from: $message", $channel->getName());

                // Parse the message for Packet offering data.
                $this->parsePacketMessage($from, $channel, $message);
            } catch (UnmappedChatLogEventException) {
                $this->console->error(
                    sprintf("Unmapped %s event for channel: \"%s\" (This usually happens due to a truncated UDP packet.)",
                        LogMapper::EVENT_MESSAGE, $channel->getName()
                    )
                );
            }

            // Do any pending operations.
            $this->operationManager->doOperations();

            // Report download progress at an interval.
            $this->downloadProgressManager->reportProgress();
        });

        $this->client->on($directMessageHandle, function (string $from, IrcChannel $channel = null, string $message) {
            if ($channel === null) {
                return;
            }

            // Update the Channel Metadata.
            $channel = $this->getChannelFromClient($channel);
            $line = $channel->getName() . " @$from: $message";

            $this->console->warn($line);

            $this->logDiverter->log(LogMapper::EVENT_MESSAGE, "{$this->nick->nick}: $message", $channel->getName());
        });
    }

    /**
     * Parses a packet message from a user in an IRC channel and processes it.
     *
     * @param string $from     The nickname of the user sending the message.
     * @param IrcChannel $channel The channel from which the message is sent.
     * @param string $message  The packet message to be parsed.
     *
     * @return void
     */
    protected function parsePacketMessage(string $from, IrcChannel $channel, string $message): void
    {
        // Retrieve an instance of the channel model.
        $channelModel = $this->getChannelByName($channel->getName());

        // Early return if the channel does not have a parent.
        if ($channelModel->parent !== null) {
            return;
        }

        // Define the error message for easier logging.
        $errorMessage = "Error parsing packet message: \"$message\"";

        // Retrieve the bot object by nickname.
        $bot = $this->getBotByNick($from);

        // Attempt to parse the message, catching and logging errors as needed.
        try {
            Parse::packet($message, $bot, $channelModel, $this->cache);
        } catch (QueryException $exception) {
            // Log error for query-related exceptions.
            $this->console->error($errorMessage);
            $this->console->error($exception->getMessage());
        } catch (NetworkWithNoChannelException $exception) {
            // Log error for network-related exceptions where no channel exists.
            $this->console->error($errorMessage);
            $this->console->error($exception->getMessage());
        }
    }

    /**
     * Handles console messages.
     *
     * @return void
     */
    public function consoleHandler(): void
    {
        $this->client->on(IrcClientEvent::CONSOLE, function (string $user, string $message) {
            $cleanMessage = Parse::cleanMessage($message);

            if ($user === $this->nick->nick) {
                $this->console->warn("[ {$this->network->name} ]: $cleanMessage");
            }

            $this->logDiverter->log(LogMapper::EVENT_CONSOLE, $cleanMessage);
        });
    }

    /**
     * Handles notices by processing different types of messages and triggering the appropriate actions.
     *
     * @return void
     */
    public function noticeHandler(): void
    {
        $this->client->on(IrcClientEvent::NOTICE, function (string $notice) {
            // Clean the notice message
            $clean = Parse::cleanMessage($notice);
            $parts = explode(' ', $clean);
            $subject = array_shift($parts);
            $txt = implode(' ', $parts);

            // If the subject isn't the nick, prepend it to the text
            if ($subject !== $this->nick->nick) {
                $txt = "$subject $txt";
            }

            // Process the notice based on its type
            if ($this->isQueuedNotification($txt)) {
                $this->processQueuedNotification($txt);
                return;
            }

            if ($this->isQueuedResponse($txt)) {
                $this->processQueuedResponse($txt);
                return;
            }

            if ($packetSearchResult = $this->extractPacketSearchResult($txt)) {
                if ($this->isValidPacketSearchResult($packetSearchResult)) {
                    $this->processPacketSearchResult($packetSearchResult);
                    return;
                }
            }

            if ($searchSummary = $this->extractSearchSummary($txt)) {
                if ($this->isValidSearchSummary($searchSummary)) {
                    $this->processSearchSummary($searchSummary);
                    return;
                }
            }

            if ($hotReportLine = $this->extractHotReportLine($txt)) {
                if ($this->isValidHotReportLine($hotReportLine)) {
                    $this->processHotReportLine($hotReportLine);
                    return;
                }
            }

            if ($hotReportSummary = $this->extractHotReportSummary($txt)) {
                if ($this->isValidHotReportSummary($hotReportSummary)) {
                    $this->processHotReportSummary($hotReportSummary);
                    return;
                }
            }

            // Log and warn if no other conditions are met
            $this->logAndWarnNotice($txt);
        });
    }

    /**
     * Determines if the message corresponds to a queued notification.
     *
     * @param string $txt
     * @return bool
     */
    protected function isQueuedNotification(string $txt): bool
    {
        return substr(trim($txt), 0, 6) === 'Queued';
    }

    /**
     * Determines if the message corresponds to a queued response.
     *
     * @param string $txt
     * @return bool
     */
    protected function isQueuedResponse(string $txt): bool
    {
        $matches = [];
        return preg_match(self::QUEUED_RESPONSE_MASK, $txt, $matches) && isset($matches[3]);
    }

    /**
     * Validates the packet search result.
     *
     * @param array $packetSearchResult
     * @return bool
     */
    protected function isValidPacketSearchResult(array $packetSearchResult): bool
    {
        return count($packetSearchResult) >= 5;
    }

    /**
     * Validates the search summary.
     *
     * @param array $searchSummary
     * @return bool
     */
    protected function isValidSearchSummary(array $searchSummary): bool
    {
        return count($searchSummary) >= 3;
    }

    /**
     * Validates the hot report line.
     *
     * @param array $hotReportLine
     * @return bool
     */
    protected function isValidHotReportLine(array $hotReportLine): bool
    {
        return count($hotReportLine) >= 5;
    }

    /**
     * Validates the hot report summary.
     *
     * @param array $hotReportSummary
     * @return bool
     */
    protected function isValidHotReportSummary(array $hotReportSummary): bool
    {
        return count($hotReportSummary) >= 3;
    }

    /**
     * Extracts the packet search result from the text.
     *
     * $packet[0] contains the entire matched string.
     * $packet[1] is the file size.
     * $packet[2] is the file name.
     * $packet[3] is the bot name.
     * $packet[4] is the packet number.
     *
     * If less than 5 elements are returned, it is not a valid packet search result.
     *
     * @param string $txt
     * @return array
     */
    protected function extractPacketSearchResult(string $txt): array
    {
        $packet = [];
        preg_match(self::REQUEST_INSTRUCTIONS_MASK, $txt, $packet);
        return $packet;
    }

    /**
     * Extracts the hot report line from the text.
     *
     * $result[0] contains the entire matched string.
     * $result[1] is the popularity rating of the first file.
     * $result[2] is the file name of the first file.
     * $result[3] is the popularity rating of the second file.
     * $result[4] is the file name of the second file.
     *
     * If less than 3 elements are returned, it is not a valid packet search result.
     *
     * @param string $txt
     * @return array
     */
    protected function extractHotReportLine(string $txt): array
    {
        $result = [];
        preg_match(self::HOT_REPORT_RESULT, $txt, $result);
        return $result;
    }

    /**
     * Extracts any search summary from the text.
     *
     * $summary[0] contains the entire matched string.
     * $summary[1] is the chatroom.
     * $summary[2] is the number of results.
     *
     * If less than 3 elements are returned, it is not a valid summary.
     *
     * @param string $txt
     * @return array
     */
    protected function extractSearchSummary(string $txt): array
    {
        $summary = [];
        preg_match(self::SEARCH_SUMMARY_MASK, $txt, $summary);
        return $summary;
    }

    /**
     * Extracts any hot report summary from the text.
     *
     * $summary[0] contains the entire matched string.
     * $summary[1] is the channel name string.
     * $summary[2] is the summary string.
     *
     * If less than 3 elements are returned, it is not a valid summary.
     *
     * @param string $txt
     * @return array
     */
    protected function extractHotReportSummary(string $txt): array
    {
        $summary = [];
        preg_match(self::HOT_REPORT_SUMMARY_MASK, $txt, $summary);
        return $summary;
    }

    /**
     * Processes a queued notification.
     *
     * @param string $txt
     * @return void
     */
    protected function processQueuedNotification(string $txt): void
    {
        $this->doQueuedStateChange($txt);
        $this->logAndWarnNotice($txt);
    }

    /**
     * Processes a queued response.
     *
     * @param string $txt
     * @return void
     */
    protected function processQueuedResponse(string $txt): void
    {
        $packet = $this->markAsQueued($txt);
        if ($packet) {
            $message = "Queued for #{$packet->number} {$packet->file_name} - {$packet->size}";
            $this->logAndWarnNotice($message);
        }
    }

    /**
     * Processes a packet search result.
     *
     * @param array $packetSearchResult
     * @return void
     */
    protected function processPacketSearchResult(array $packetSearchResult): void
    {
        [, $fileSize, $fileName, $nick, $packetNumber] = $packetSearchResult;
        $message = "$nick   $packetNumber - $fileSize $fileName";
        $this->logAndWarnNotice($message);

        try {
            $this->makeSearchResult($fileName, $fileSize, $nick, $packetNumber);
        } catch(NetworkWithNoChannelException) {
            $this->console->warn("Unable to map $nick to a channel [NetworkWithNoChannelException]");
        }
    }

    /**
     * Processes a search summary.
     *
     * @param array $searchSummary
     * @return void
     */
    protected function processSearchSummary(array $searchSummary): void
    {
        [, $channelName, $numberResults] = $searchSummary;
        $message = "Found $numberResults results in $channelName";
        $this->logAndWarnNotice($message);
        $this->makeSearchSummary($channelName);
    }

    /**
     * Processes a hot report line.
     *
     * @param array $hotReportLine
     * @return void
     */
    protected function processHotReportLine(array $hotReportLine): void
    {
        [, $hotReportRank1, $hotReportFileName1, $hotReportRank2, $hotReportFileName2] = $hotReportLine;
        $this->makeHotReportLine($hotReportRank1, $hotReportFileName1);

        if ($hotReportRank2 !== null && $hotReportFileName2 !== null) {
            $this->makeHotReportLine($hotReportRank2, $hotReportFileName2);
            $spacer = $this->dynamicWordSpacing($hotReportFileName1, self::LINE_COLUMN_SPACES);
            $message =  "[$hotReportRank1] $hotReportFileName1 $spacer [$hotReportRank2] $hotReportFileName2";
        } else {
            $message =  "[$hotReportRank1] $hotReportFileName1";
        }

        $this->logAndWarnNotice($message);
    }

    /**
     * Processes a hot report summary.
     *
     * @param array $hotReportSummary
     * @return void
     */
    protected function processHotReportSummary(array $hotReportSummary): void
    {
        [, $channelName, $hotReportSummaryStr] = $hotReportSummary;
        $channelNameSanitized = strtolower($channelName);
        $message =  "$channelNameSanitized $hotReportSummaryStr";
        $this->logAndWarnNotice($message);
        $this->makeHotReportSummary($channelNameSanitized, $hotReportSummaryStr);
    }

    /**
     * Logs and warns a notice message.
     *
     * @param string $message
     * @return void
     */
    protected function logAndWarnNotice(string $message): void
    {
        $this->console->warn("========[  $message ");
        $this->logDiverter->log(LogMapper::EVENT_NOTICE, $message);
    }

    /**
     * Create a Hot Report line and dispatches an event if a Hot Report exists.
     *
     * @param float $rating The rating associated with the hot report line.
     * @param string $term The term associated with the hot report line.
     * @return void
     */
    protected function makeHotReportLine(float $rating, string $term): void
    {
        // Retrieve the most recently created Hot Report, ensuring it's only fetched once.
        $hotReport = HotReport::latest('id')->first();

        // If a Hot Report exists, create a new Hot Report Line and dispatch the associated event.
        if ($hotReport !== null) {
            $hotReportLine = HotReportLine::create([
                'hot_report_id' => $hotReport->id,
                'rating'        => $rating,
                'term'          => $term,
            ]);

            HotReportLineEvent::dispatch($hotReportLine);
        }
    }

    /**
     * Creates a Hot Report summary and dispatches the associated event.
     *
     * @param string $channelName The name of the channel for the hot report.
     * @param string $summary The summary content of the hot report.
     * @return void
     */
    public function makeHotReportSummary(string $channelName, string $summary): void
    {
        $channel = $this->getChannelByName($channelName);

        // If the channel exists, create a Hot Report and dispatch an event.
        if ($channel) {
            $hotReport = HotReport::create([
                'channel_id' => $channel->id,
                'summary'    => $summary,
            ]);

            HotReportSummaryEvent::dispatch($hotReport);
        }
    }

    /**
     * Creates a search result and dispatches the associated event.
     *
     * @param string $fileName The name of the file in the search result.
     * @param string $fileSize The size of the file in the search result.
     * @param string $nick The bot name that generated the search result.
     * @param string $packetNumber The packet number associated with the result.
     * @return void
     */
    protected function makeSearchResult(string $fileName, string $fileSize, string $nick, string $packetNumber): void
    {
        // Resolve the packet based on the search parameters.
        $packet = $this->resolvePacket($fileName, $fileSize, $nick, $packetNumber);

        // Create a new PacketSearchResult entry.
        $packetSearchResult = PacketSearchResult::create([
            'packet_id' => $packet->id,
        ]);

        // Dispatch an event for the created search result.
        PacketSearchResultEvent::dispatch($packetSearchResult);
    }

    /**
     * Creates a search summary for the specified channel and dispatches the associated event.
     *
     * @param string $channelName The name of the channel for which the search summary is created.
     * @return void
     */
    protected function makeSearchSummary(string $channelName): void
    {
        // Create or find the channel.
        $channel = $this->getChannelByName($channelName);

        if (null !== $channel) {

            // Create a new PacketSearch entry for the channel.
            $packetSearch = PacketSearch::create([
                'channel_id' => $channel->id,
            ]);

            // Dispatch an event for the created search summary.
            PacketSearchSummaryEvent::dispatch($packetSearch);
        }
    }

    /**
     * Locates or creates a packet object based on the input criteria.
     *
     * @param string $fileName
     * @param string $fileSize
     * @param string $nick
     * @param string $packetNumber
     * @return Packet
     */
    protected function resolvePacket(string $fileName, string $fileSize, string $nick, string $packetNumber): Packet
    {
        // Fetch the bot, or create it if it doesn't exist
        $bot = $this->getBotByNick($nick);

        // Get the channel associated with the bot
        $channel = $this->getBotChannelByBestGuess($bot);

        // Use MediaTypeGuesser to determine the media type for the file
        $mediaType = (new MediaTypeGuesser($fileName))->guess();

        // Use updateOrCreate to find or create the packet record efficiently
        return Packet::updateOrCreate(
            [
                'number' => $packetNumber,
                'network_id' => $this->network->id,
                'channel_id' => $channel->id,
                'bot_id' => $bot->id,
            ],
            [
                'file_name' => $fileName,
                'size' => $fileSize,
                'media_type' => $mediaType,
            ]
        );
    }


    /**
     * Makes a best guess at which channel a bot may represent in the absence of a channel name.
     *
     * @param Bot $bot
     * @return Channel
     */
    protected function getBotChannelByBestGuess(Bot $bot): Channel
    {
        $botId = $bot->id;

        if (isset($this->botChannelMap[$botId])) {
            return $this->botChannelMap[$botId];
        }

        $this->botChannelMap[$botId] = Packet::where('bot_id', $botId)->latest()->value('channel')
            ?? Packet::where('network_id', $bot->network->id)->latest()->value('channel')
            ?? Channel::where('network_id', $bot->network->id)->first();

        if ($this->botChannelMap[$botId] === null) {
            throw new NetworkWithNoChannelException('No channel found for network: ' . $bot->network->name);
        }

        return $this->botChannelMap[$botId];
    }

    /**
     * Parses the response that a download was queued.
     *
     * @param string $txt
     * @return Packet|null
     */
    protected function markAsQueued(string $txt): ?Packet
    {
        $var = env('VAR', '/usr/var');
        $downloadDir = "{$var}/download";

        // Extract packet number, file name, and position from the text
        [$packetNumber, $file, $position] = $this->extractQueuedResponse($txt);

        // Fetch the latest packet matching the packet number and file name
        $packet = Packet::where('number', $packetNumber)
            ->where('file_name', $file)
            ->orderByDesc('created_at')
            ->first();

        if ($packet) {
            // Create or update the download record with the queued status
            Download::updateOrCreate(
                ['file_uri' => "{$downloadDir}/{$file}", 'packet_id' => $packet->id],
                [
                    'status'        => Download::STATUS_QUEUED,
                    'file_name'     => $packet->file_name,
                    'queued_status' => $position,
                    'meta'          => $packet->meta,
                    'media_type'    => $packet->media_type
                ]
            );

            // If the file isn't locked, lock it and queue a job to check if it's finished downloading
            if (!$this->isFileDownloadLocked($file)) {
                $this->lockFile($file);

                // Queue the job that checks if the file is finished downloading
                $timeStamp = new DateTime('now');
                CheckFileDownloadCompleted::dispatch($file, $timeStamp)
                    ->delay(now()->addMinutes(CheckFileDownloadCompleted::SCHEDULE_INTERVAL));
            }
        }

        return $packet;
    }

    /**
     * Parses the DCC queue and updates the download state.
     *
     * @param string $txt
     * @return void
     */
    protected function doQueuedStateChange(string $txt): void
    {
        // Define the directory path where downloads are stored
        $var = env('VAR', '/usr/var');
        $downloadDir = "{$var}/download";

        // Extract file name, position, and total from the queued state text
        [$file, $position, $total] = $this->extractQueuedState($txt);

        // Fetch the latest download record for the specified file
        $download = Download::where('file_uri', "{$downloadDir}/{$file}")
            ->orderByDesc('created_at')
            ->first();

        // Only update the queued state if the download exists and is not completed
        if ($download && $download->status !== Download::STATUS_COMPLETED) {
            $download->queued_status = $position;
            $download->queued_total = $total;
            $download->save();

            // If the file isn't locked, lock it and queue a job to check download completion
            if (!$this->isFileDownloadLocked($file)) {
                $this->lockFile($file);

                // Queue the job to check if the file has finished downloading
                $timeStamp = new DateTime('now');
                CheckFileDownloadCompleted::dispatch($file, $timeStamp)
                    ->delay(now()->addMinutes(CheckFileDownloadCompleted::SCHEDULE_INTERVAL));
            }
        }
    }

    /**
     * Extracts the file, position, and total values from the given text.
     *
     * @param string $txt
     * @return array [file, position, total]
     */
    protected function extractQueuedState(string $txt): array
    {
        $matches = [];
        $file = $position = $total = null;

        // Perform the regular expression match and extract the values
        if (preg_match(self::QUEUED_MASK, $txt, $matches)) {
            // Assign values to the variables if they exist
            $file = $matches[1] ?? $file;
            $position = $matches[2] ?? $position;
            $total = $matches[3] ?? $total;
        }

        return [$file, $position, $total];
    }

    /**
     * Extracts the packet number, file, and position values from the given text.
     *
     * @param string $txt
     * @return array [packetNum, file, position]
     */
    protected function extractQueuedResponse(string $txt): array
    {
        $matches = [];
        $packetNum = $file = $position = null;

        // Perform the regular expression match and extract the values
        if (preg_match(self::QUEUED_RESPONSE_MASK, $txt, $matches)) {
            // Assign values to the variables if they exist
            $packetNum = $matches[1] ?? $packetNum;
            $file = $matches[2] ?? $file;
            $position = $matches[3] ?? $position;
        }

        return [$packetNum, $file, $position];
    }

    /**
     * Handles name events.
     *
     * @return void
     */
    public function namesHandler(): void
    {
        $this->client->on(IrcClientEvent::NAMES, function (IrcChannel $channel, array $names) {
            $channelName = $channel->getName();
            $this->updateChannel($channelName);
        });

        foreach($this->client->getChannels() as $channel) {
            $channelName = $channel->getName();
            $namesChannelHandle = IrcClientEvent::NAMES . $channelName;

            $this->client->on($namesChannelHandle, function (array $names) use ($channelName) {
                $this->updateChannel($channelName);
            });
        }
    }

    /**
     * Removes all non-numeric characters from a string.
     *
     * @param string $txtStr
     * @return string
     */
    public function cleanNumericStr(string $txtStr): string
    {
        // Filter the string to keep only numeric characters
        return implode('', array_filter(str_split($txtStr), fn($char) => ctype_digit($char)));
    }

    /**
     * Connects to the server and initializes event listening.
     *
     * @return void
     */
    public function connect(): void
    {
        try {
            $this->client->connect();
        } catch (ParseChannelNameException $e) {
            $this->console->error(str_repeat('*', 92));
            $this->console->error("           " . $e->getMessage());
            $this->console->error(str_repeat('*', 92));
        }
    }

    /**
     * Procedure for when the connection to a network has terminated.
     *
     * @return void
     */
    protected function terminateInstance(): void
    {
        $message = "Connection to: {$this->network->name} terminated.";
        $this->instance->isConnected = false;
        $this->instance->save();
        $this->logDiverter->log(LogMapper::EVENT_CLOSE, $message);

        // Use str_repeat to optimize the construction of the separator line
        $separator = str_repeat('*', 92);
        $this->console->error($separator);
        $this->console->error("           ================[  $message  ]================");
        $this->console->error($separator);
    }

    /**
     * Retrieves a channel model object by its name.
     *
     * @param string $name The name of the channel.
     * @return Channel|null The channel model or null if not found.
     */
    protected function getChannelByName(string $name): ?Channel
    {
        // Return cached channel if available
        if (isset($this->channels[$name])) {
            return $this->channels[$name];
        }

        // Retrieve the channel from the database and cache it
        $channel = Channel::where('name', $name)->first();

        if ($channel !== null) {
            $this->channels[$name] = $channel;
        }

        return $channel;
    }

    /**
     * Returns a Bot model object with the parameter of the bot nick.
     *
     * @param string $nick
     * @return Bot
     */
    public function getBotByNick(string $nick): Bot
    {
        // Return cached channel if available
        if (isset($this->bots[$nick])) {
            return $this->bots[$nick];
        }

        $this->bots[$nick] = Bot::updateOrCreate(
            [ 'network_id' => $this->network->id, 'nick' => $nick ]
        );

        return $this->bots[$nick];
    }

    /**
     * Retrieves all parent channels for a given network.
     *
     * A parent channel is defined as a channel with no parent (`channel_id` is null).
     * Only enabled channels are included in the result.
     *
     * @param Network $network The network for which parent channels are retrieved.
     * @return Collection A collection of parent channels for the specified network.
     */
    protected function getParentChannelsForNetwork(Network $network): Collection
    {
        return Channel::whereNull('channel_id')
            ->where('network_id', $network->id)
            ->where('enabled', true)
            ->get();
    }

    /**
     * Generates a string of spaces to dynamically adjust word spacing.
     *
     * The number of spaces generated is based on the total desired space minus the length of the word.
     *
     * @param string $word The word to be spaced.
     * @param int $totalSpaces The total space the word should occupy, including the word length.
     * @return string A string of spaces that will fill the remaining space after the word.
     */
    protected function dynamicWordSpacing(string $word, int $totalSpaces): string
    {
        $numSpaces = max(0, $totalSpaces - strlen($word)); // Ensure no negative spaces are generated
        return str_repeat(' ', $numSpaces); // More efficient string repetition
    }

    /**
     * Checks if a download lock exists for the given file.
     * A download lock prevents the file from being downloaded simultaneously from multiple sources.
     *
     * @param string $fileName The name of the file to check for a download lock.
     * @return bool True if the file is locked, false otherwise.
     */
    protected function isFileDownloadLocked(string $fileName): bool
    {
        return FileDownloadLock::where('file_name', $fileName)->exists();
    }

    /**
     * Locks the specified file to prevent multiple simultaneous downloads.
     *
     * @param string $file The name of the file to lock.
     * @return void
     */
    protected function lockFile(string $file): void
    {
        // Create a download lock for the file to prevent concurrent downloads.
        FileDownloadLock::create(['file_name' => $file]);
    }

    /**
     * Updates the channel information, which can be either a string (channel name) or an IrcChannel object.
     * If the channel is valid, updates its topic, user count, and metadata.
     *
     * @param IrcChannel|string $channelName The channel to update (either an IrcChannel object or a channel name).
     * @param bool $updateClient Whether to update the client after the channel update.
     * @return IrcChannel The updated IrcChannel object.
     */
    protected function updateChannel(IrcChannel|string $channelName, bool $updateClient = true): IrcChannel
    {
        // Retrieve the channel object from the client using the provided channel name or object.
        $ircChannel = $this->getChannelFromClient($channelName);

        // Convert the channel's metadata to an array and extract useful information.
        $meta = $ircChannel->toArray();
        $userCount = count($meta['users']);
        $topic = mb_convert_encoding($meta['topic'], self::SUPPORTED_ENCODING);
        $name = $meta['name'];

        // Only proceed with the update if the topic is not null and there are users.
        if ($topic !== null && $userCount > 0) {
            // Update or create the channel record in the database.
            $this->channels[$name] = Channel::updateOrCreate(
                ['name' => $name],
                ['topic' => $topic, 'users' => $userCount, 'meta' => $meta]
            );
        }

        // Optionally update the client after the channel update.
        if ($updateClient) {
            $this->updateClient();
        }

        return $ircChannel;
    }

    /**
     * Updates the data representation of the IRC client, including its connection status.
     *
     * @return void
     */
    protected function updateClient(): void
    {
        // Retrieve the client's metadata as an array.
        $meta = $this->client->toArray();

        // Extract the connection status from the metadata, default to false if not set.
        $isConnected = $meta['connection']['is_connected'] ?? false;

        // Update the client model's metadata and connection status.
        $this->clientModel->meta = $meta;
        $this->clientModel->instance->is_connected = $isConnected;

        // Persist the updated client model to the database.
        $this->clientModel->save();
    }

    /**
     * Retrieves the IrcChannel instance from the client using either a channel name string or an IrcChannel instance.
     *
     * @param IrcChannel|string $channelName
     * @return IrcChannel
     */
    protected function getChannelFromClient(IrcChannel|string $channelName): IrcChannel
    {
        // If the provided value is an IrcChannel instance, retrieve its name, otherwise assume it's already a string.
        $channelName = $channelName instanceof IrcChannel ? $channelName->getName() : $channelName;

        // Fetch and return the channel from the client using the channel name.
        return $this->client->getChannel($channelName);
    }

}
