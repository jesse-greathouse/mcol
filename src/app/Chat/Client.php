<?php

namespace App\Chat;

use Illuminate\Console\Command;

use Jerodev\PhpIrcClient\IrcClient,
    Jerodev\PhpIrcClient\IrcChannel,
    Jerodev\PhpIrcClient\Options\ClientOptions;

use App\Chat\Log\Diverter as LogDiverter,
    App\Chat\Log\Mapper as LogMapper,
    App\Events\HotReportLine as HotReportLineEvent,
    App\Events\HotReportSummary as HotReportSummaryEvent,
    App\Events\PacketSearchResult as PacketSearchResultEvent,
    App\Events\PacketSearchSummary as PacketSearchSummaryEvent,
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

use \DateTime;
use \TypeError;

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
     * An lookup table of instantiated Channel Models associated with this client.
     * Keeps instantiated channels in memory so we don't have to keep hitting the DB.
     *
     * @var array<string, Channel>
     */
    protected array $channels = [];

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

    public function __construct(Nick $nick, Network $network, Command $console) {
        $logRoot = env('LOG_DIR', '/var/log');
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
     * Opens a new instance.
     *
     * @return void
     */
    protected function registerInstance()
    {
        $logUri = $this->logDiverter->getInstanceUri();
        $pid = ($pid = getmypid()) ? $pid : null;
        $meta = $this->client->toArray();
        $isConnected = false;
        if (isset($meta['connection']) && isset($meta['connection']['is_connected'])) {
            $isConnected = $meta['connection']['is_connected'];
        }

        $this->instance =  Instance::updateOrCreate(
            ['client_id' => $this->clientModel->id],
            ['is_connected' => $isConnected, 'log_uri' => $logUri, 'pid' => $pid]
        );

        $this->operationManager = new OperationManager($this->client, $this->instance, $this->console);
        $this->downloadProgressManager = new DownloadProgressManager($this->instance, $this->console);

        $this->disconnectHandler();
    }

    /**
     * Handles Ping events.
     *
     * @return void
     */
    public function pingHandler(): void
    {
        $this->client->on('ping', function(string $pinger) {

            // Checks if the pinger string is a masked IP.
            try {
                $ip = long2ip($pinger);
                if (false !== $ip) {
                    $pinger = $ip;
                }
            } catch(TypeError) {
                // Do nothing. TypeError will happen if pinger cant be converted.
            }

            $pingMsg = $pinger . ' PING -> ' . $this->nick->nick;
            $pongMsg = $this->nick->nick . ' PONG -> ' . $pinger;

            // The response actually happens in the Message object: Jerodev\PhpIrcClient\Messages\PingMessage
            // This is just showing that something happened in the log.
            $this->console->info($pingMsg);
            $this->console->info($pongMsg);
            $this->logDiverter->log(LogMapper::EVENT_PING, $pingMsg);
            $this->logDiverter->log(LogMapper::EVENT_PING, $pongMsg);
        });
    }

    /**
     * Handles a join event.
     *
     * @return void
     */
    public function joinHandler(): void
    {
        $this->client->on('joinInfo', function(string $user, string $channelName) {
            $message = "$user joined";
            $this->console->info("$message $channelName");
            $this->logDiverter->log(LogMapper::EVENT_JOIN, $message, $channelName);
        });
    }

    /**
     * Handles a kick event.
     *
     * @return void
     */
    public function kickHandler(): void
    {
        $this->client->on('kick', function($channel, string $user, string $kicker, $message) {
            $channel = $this->updateChannel($channel);
            $channelName = $channel->getName();
            $message = "$user was kicked from $channelName by $kicker. Reason:$message";
            $this->console->error($message);
            $this->logDiverter->log(LogMapper::EVENT_KICK, $message, $channelName);
        });
    }

    /**
     * Handles a nick change event.
     *
     * @return void
     */
    public function nickHandler(): void
    {
        $this->client->on('nick', function(string $nick, string $newNick) {
            $message = "$nick sets nick: $newNick";
            $this->console->warn($message);
            $this->logDiverter->log(LogMapper::EVENT_NICK, $message);
        });
    }

    /**
     * Handles a quit event.
     *
     * @return void
     */
    public function quitHandler(): void
    {
        $this->client->on('quit', function(string $user, string $reason) {
            $quit = 'Quit: ';

            if (false !== strpos($reason, trim($quit))) {
                $quit = '';
            }

            $message = "$user $quit$reason";

            $this->console->warn($message);
            $this->logDiverter->log(LogMapper::EVENT_QUIT, $message);
        });
    }

    /**
     * Handles a part event.
     *
     * @return void
    */
   public function partHandler(): void
   {
       $this->client->on('part', function(string $user, $channel, string $reason) {
            $channel = $this->updateChannel($channel);
            $channelName = $channel->getName();
            $message = "$user parted: $reason";
            $this->console->warn("$user parted $channelName: $reason");
            $this->logDiverter->log(LogMapper::EVENT_PART, $message, $channelName);
       });
   }

    /**
     * Handles a mode change event.
     *
     * @return void
    */
    public function modeHandler(): void
    {
        $this->client->on('mode', function($channel, string $user, string $mode) {
            $channelName = '';
            if (null !== $channel && '' !== $channel) {
                $channel = $this->updateChannel($channel);
                $channelName = $channel->getName();
            }

            $message = trim("$user mode: $mode");
            $this->console->warn("$channelName set $message");

            if ('' === $channelName) {
                $this->logDiverter->log(LogMapper::EVENT_NOTICE, $message);
            } else {
                $this->logDiverter->log(LogMapper::EVENT_MODE, $message, $channelName);
            }

        });
    }

    /**
     * Handles an invite event.
     *
     * @return void
    */
    public function inviteHandler(): void
    {
        $this->client->on('invite', function($channel, string $user) {
            $channelName = $channel->getName();
            $message = "$user has invited {$this->nick->nick} to join channel: $channelName";
            $this->console->warn("========[ $message");
            $this->logDiverter->log(LogMapper::EVENT_INVITE, $message, $channelName);
        });
    }

    /**
     * Handles a topic change event.
     *
     * @return void
     */
    public function topicHandler(): void
    {
        $this->client->on('topic', function($channel, string $topic) {
            $channel = $this->updateChannel($channel);
            $channelName = $channel->getName();

            $this->console->info("");
            $this->console->info("                ================[  $channelName Topic ]================");
            $this->console->info("$topic");
            $this->console->info("");

            $this->logDiverter->log(LogMapper::EVENT_TOPIC, $topic, $channelName);
        });
    }

    /**
     * Handles a dcc event.
     *
     * @return voids
     */
    public function dccHandler(): void
    {
        $this->client->on('dcc', function($action, $fileName, $ip, $port, $fileSize) {
            $message = "A DCC event has been sent, with the following information";
            $information = "action $action, fileName: $fileName, ip: $ip, port: $port, fileSize: $fileSize";
            $this->console->warn("$message:");
            $this->console->warn("$information");
            $this->logDiverter->log(LogMapper::EVENT_DCC, "$message: $information");
        });
    }

    /**
     * Handles the registered event (connected).
     *
     * @return void
     */
    public function registeredHandler(): void
    {
        $this->client->on('registered', function(string $server, string $user, string $message, string $hostMask) {
            $this->connectedServer = $server;
            $this->hostMask = $hostMask;
            $notice = "$user connected to: $server";
            $this->console->warn($notice);
            $this->logDiverter->log(LogMapper::EVENT_REGISTERED, $notice);

            $this->console->info($message);
            $this->logDiverter->log(LogMapper::EVENT_REGISTERED, $message);

            $this->registerInstance();

            $channels = $this->getAllParentChannelsForNetwork($this->network);

            foreach($channels as $channel) {
                $this->logDiverter->addChannel($channel->name);
                $this->client->join($channel->name);
                $this->updateChannel($channel->name, false);
                foreach($channel->children as $child) {
                    $this->logDiverter->addChannel($child->name);
                    $this->client->join($child->name);
                    $this->updateChannel($child->name, false);
                }
            }

            $this->updateClient();
        });
    }

    /**
     * Handles the event of when the client disconnects.
     *
     * @return void
     */
    public function disconnectHandler(): void
    {
        $clientConnection = $this->client->getConnection();
        $tcpConnection = $clientConnection->getConnection();

        // Handles intentional close of the connection
        $this->client->on('close', function() {
           $this->terminateInstance();
        });

        // Handles disconnect not itiated by client.
        $tcpConnection->on('close', function () use ($clientConnection) {
            $clientConnection->setConnected(false);
            $this->terminateInstance();
        });

        $tcpConnection->on('end', function () use ($clientConnection) {
            $clientConnection->setConnected(false);
            $this->terminateInstance();
        });
    }

    /**
     * Handles Private Messages
     *
     * @return void
     */
    public function versionHandler(): void
    {
        $this->client->on('version', function () {
            $message = "VERSION ". $this->client->getVersion();
            $this->console->warn($message);
            $this->logDiverter->log(LogMapper::EVENT_VERSION, $message);
        });
    }

    /**
     * Handles Private Messages
     *
     * @return void
     */
    public function ctcpHandler(): void
    {
        $this->client->on('ctcp', function (string $action, array $args, string $command) {
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
        $this->client->on('privmsg', function (string $userName, $target, string $message) {

            // If the Message string is empty don't bother parsing, just warn in the console.
            if (strlen($message) < 1) {
                $this->console->warn("Empty message from $userName to $target");
                return;
            }

            $this->logDiverter->log(LogMapper::LOG_PRIVMSG, "$userName: $message");
            $this->console->warn("$userName to $target says: $message");

            # VERSION
            if (false !== strpos($message, 'VERSION')) {
                $message .= ' ' . self::VERSION;
                $this->client->say($userName, self::VERSION);
                $this->console->warn($message);
                $this->logDiverter->log(LogMapper::EVENT_VERSION, $message);
                return;
            }

            # DCC SEND PROTOCOL
            if (false !== strpos($message, 'DCC SEND')) {
                // $message is a string like: "DCC SEND Frasier.2023.S01E04.1080p.WEB.h264-ETHEL.mkv 1311718603 58707 2073127114"
                [, , $fileName, $ip, $port, $fileSize] = explode(' ', $message);
                $fileSizeCln = $this->clnNumericStr($fileSize);
                $ipCln = $this->clnNumericStr($ip);
                $portCln = $this->clnNumericStr($port);
                $uri = env('DOWNLOAD_DIR', '/var/download') . "/$fileName";

                if (file_exists($uri)) {
                    $position = filesize($uri);
                    if (false !== $position) {
                        $positionCln = $this->clnNumericStr($position);
                        DccDownload::dispatch($ipCln, $portCln, $fileName, $positionCln, $userName, $positionCln)->onQueue('download');
                        $notice = "Queued to resume DCC Download Job: host: $ipCln port: $portCln file: $fileName file-size: $positionCln bot: '$userName' resume: $positionCln";
                        $this->console->warn($notice);
                        $this->logDiverter->log(LogMapper::EVENT_NOTICE, $notice);
                    } else {
                        unlink($uri);
                    }
                } else {
                    DccDownload::dispatch($ipCln, $portCln, $fileName, $fileSizeCln, $userName)->onQueue('download');
                    $notice = "Queued DCC Download Job: host: $ipCln port: $portCln file: $fileName file-size: $fileSizeCln bot: '$userName'";
                    $this->console->warn($notice);
                    $this->logDiverter->log(LogMapper::EVENT_NOTICE, $notice);
                }

                return;
            }

            if (false !== strpos($message, 'DCC ACCEPT')) {
                [, , $fileName, $ip, $port, $position] = explode(' ', $message);
                $positionCln = $this->clnNumericStr($position);
                $ipCln = $this->clnNumericStr($ip);
                $portCln = $this->clnNumericStr($port);
                $uri = env('DOWNLOAD_DIR', '/var/download') . "/$fileName";
                $this->console->warn($message);
                $this->logDiverter->log(LogMapper::EVENT_NOTICE, $message);
                $notice = "Queued to resume DCC Download Job: host: $ipCln port: $portCln file: $fileName file-size: $positionCln bot: '$userName' resume: $positionCln";
                DccDownload::dispatch($ipCln, $portCln, $fileName, $positionCln, $userName, $positionCln)->onQueue('download');
                $this->console->warn($notice);
                $this->logDiverter->log(LogMapper::EVENT_NOTICE, $notice);
                return;
            }

            if (false !== strpos($message, 'DCC')) {
                $notice = "Unhandled DCC Action: $message";
                $this->console->warn("||||||||||||| ===> $notice");
                $this->logDiverter->log(LogMapper::EVENT_NOTICE, $notice);
                return;
            }

            $notice = "Unhandled PRIVMSG from $userName: $message";
            $this->console->warn("||||||||||||| ===> $notice");
            $this->logDiverter->log(LogMapper::EVENT_NOTICE, $notice);

            // Try to send a message back to the user.
            // Don't be rude :-)
            if ('' !== $userName && (null !== $target) && (trim($target) === $this->nick->nick)) {
                $this->client->say($target, "Hello, $target. Sorry, I can't get back to you. This application doesn't actively monitor the chat. If I have done something wrong, please kindly raise an issue on my github: https://github.com/jesse-greathouse/mcol/issues");
                $this->client->say($target, self::VERSION);
            }

            return;
        });
    }

    /**
     * Handles standard messages in channel.
     *
     * @return void
     */
    public function motdHandler(): void
    {
        $this->client->on('motd', function (string $message) {
            $clean = Parse::cleanMessage($message);
            $this->console->warn("[ {$this->network->name} ]: $clean");
            $this->logDiverter->log(LogMapper::EVENT_PING, $clean);
        });
    }

    /**
     * Handles standard messages in channel.
     *
     * @return void
     */
    public function messageHandler(): void
    {
        $this->client->on('message', function (string $from, IrcChannel $channel = null, string $message) {
            $line = '';

            if (null !== $channel) {
                // Update the Channel Metadata.
                $channel = $this->getChannelFromClient($channel);
                $line .= $channel->getName();
            } else {
                return;
            }

            $line .= " @$from: $message";

            $this->logDiverter->log(LogMapper::EVENT_MESSAGE, "$from: $message", $channel->getName());

            # Do any pending operations.
            $this->operationManager->doOperations();

            # Report download progress at an interval.
            $this->downloadProgressManager->reportProgress();
        });

        $this->client->on("message{$this->nick->nick}", function (string $from, IrcChannel $channel = null, string $message) {
            $line = '';

            if (null !== $channel) {
               // Update the Channel Metadata.
               $channel = $this->getChannelFromClient($channel);
               $line .= $channel->getName();
            }

            $line .= " @$from: $message";

            $this->console->warn($line);

            $this->logDiverter->log(LogMapper::EVENT_MESSAGE, "{$this->nick->nick}: $message", $channel->getName());
        });
    }

    /**
     * Handles console messages.
     *
     * @return void
     */
    public function consoleHandler(): void
    {
        $this->client->on('console', function (string $user, string $message) {
            $clean = Parse::cleanMessage($message);
            if ($user === $this->nick->nick) {
                $this->console->warn("[ {$this->network->name} ]: $clean");
            }

            $this->logDiverter->log(LogMapper::EVENT_CONSOLE, $clean);
        });
    }

    /**
     * Handles notices.
     *
     * @return void
     */
    public function noticeHandler(): void
    {
        $this->client->on('notice', function (string $notice) {
            $clean  = Parse::cleanMessage($notice);
            $parts = explode(' ', $clean);
            $subject = array_shift($parts);
            $txt = implode(' ', $parts);

            if ($subject !== $this->nick->nick) {
                $txt = "$subject $txt";
            }

            if ($this->isQueuedNotification($txt)) {
                $this->doQueuedStateChange($txt);
                $this->console->warn("========[  $txt ");
                $this->logDiverter->log(LogMapper::EVENT_NOTICE, $txt);
                return;
            } else if ($this->isQueuedResponse($txt)) {
                $packet = $this->markAsQeueued($txt);
                if ($packet) {
                    $message = "Queued for #{$packet->number} {$packet->file_name} - {$packet->size}";
                    $this->console->warn("========[  $message");
                    $this->logDiverter->log(LogMapper::EVENT_NOTICE, $message);
                }
                return;
            }

            ## Check to see if this is a search result.
            $packetSearchResult = $this->extractPacketSearchResult($txt);
            if ($this->isPacketSearchResult($packetSearchResult)) {
                [, $fileSize, $fileName, $botName, $packetNumber] = $packetSearchResult;
                $message = "$botName   $packetNumber - $fileSize $fileName";
                $this->console->warn("==[  > $message");
                $this->logDiverter->log(LogMapper::EVENT_NOTICE, $message);
                $this->searchResultHandler($fileName, $fileSize, $botName, $packetNumber);
                return;
            }

            ## Check to see if this is a search summary.
            $searchSummary = $this->extractSearchSummary($txt);
            if ($this->isSearchSummary($searchSummary)) {
                [, $channelName, $numberResults] = $searchSummary;
                $message = "Found $numberResults results in $channelName";
                $this->console->warn("========[  $message");
                $this->logDiverter->log(LogMapper::EVENT_NOTICE, $message);
                $this->searchSummaryHandler($channelName);
                return;
            }

             ## Check to see if this is a Hot Report result.
             $hotReportLine = $this->extractHotReportLine($txt);
             if ($this->isHotReportLine($hotReportLine)) {
                 [, $hotReportRank1, $hotReportFileName1, $hotReportRank2, $hotReportFileName2] = $hotReportLine;
                 $this->hotReportLineHandler($hotReportRank1, $hotReportFileName1);

                 if ($hotReportRank2 !== null && $hotReportFileName2 !== null) {
                    $this->hotReportLineHandler($hotReportRank2, $hotReportFileName2);
                    $spacer = $this->dynamicWordSpacing($hotReportFileName1, self::LINE_COLUMN_SPACES);
                    $message =  "[$hotReportRank1] $hotReportFileName1 $spacer [$hotReportRank2] $hotReportFileName2";
                    $this->console->warn("==[   $message");
                    $this->logDiverter->log(LogMapper::EVENT_NOTICE, $message);
                 } else {
                    $message =  "[$hotReportRank1] $hotReportFileName1";
                    $this->console->warn("==[   $message");
                    $this->logDiverter->log(LogMapper::EVENT_NOTICE, $message);
                 }

                 return;
             }

            ## Check to see if this is a Hot Report summary.
            $hotReportSummary = $this->extractHotReportSummary($txt);
            if ($this->isHotReportSummary($hotReportSummary)) {
                [, $channelName, $hotReportSummaryStr] = $hotReportSummary;
                $channelNameSanitized = strtolower($channelName);
                $message =  "$channelNameSanitized $hotReportSummaryStr";
                $this->console->warn("========[  $message");
                $this->logDiverter->log(LogMapper::EVENT_NOTICE, $message);
                $this->hotReportSummaryHandler($channelNameSanitized, $hotReportSummaryStr);
                return;
            }

            $this->console->warn("========[  $txt ");
            $this->logDiverter->log(LogMapper::EVENT_NOTICE,$txt);
        });
    }

    /**
     * Handles Hot Report Lines identified in events.
     *
     * @param float $rating
     * @param string $term
     * @return void
     */
    public function hotReportLineHandler(float $rating, string $term): void
    {
        // Get the most recently created Hot Report
        $hotReport =  HotReport::orderByDesc('id')->first();

        // If a Hot Report id was obtained, add this line.
        if (null !== $hotReport) {
            $hotReportLine = HotReportLine::create([
                'hot_report_id' => $hotReport->id,
                'rating'        => $rating,
                'term'          => $term,
            ]);
            HotReportLineEvent::dispatch($hotReportLine);
        }
    }

    /**
     * Handles Hot Report Summary results.
     *
     * @param string $channelName
     * @param string $summary
     * @return void
     */
    public function hotReportSummaryHandler(string $channelName, string $summary): void
    {
        $channel = Channel::where('name', $channelName)
            ->where('network_id', $this->network->id)
            ->first();

        if (null !== $channel) {
            $hotReport = HotReport::create([
                'channel_id'    => $channel->id,
                'summary'       => $summary,
            ]);

            HotReportSummaryEvent::dispatch($hotReport);
        }
    }

    /**
     * Handles Search results identified in events.
     *
     * @param string $fileName
     * @param string $fileSize
     * @param string $botName
     * @param string $packetNumber
     * @return void
     */
    public function searchResultHandler(string $fileName, string $fileSize, string $botName, string $packetNumber): void
    {
        $packet = $this->resolvePacket($fileName, $fileSize, $botName, $packetNumber);
        $packetSearchResult = PacketSearchResult::create([
            'packet_id' => $packet->id,
        ]);
        PacketSearchResultEvent::dispatch($packetSearchResult);
    }

    /**
     * Handles Search Summary results.
     *
     * @param string $channelName
     * @return void
     */
    public function searchSummaryHandler(string $channelName): void
    {
        $channel = Channel::firstOrCreate([
            'name' => $channelName,
            'network_id' => $this->network->id,
        ]);

        $packetSearch = PacketSearch::create([
            'channel_id' => $channel->id,
        ]);

        PacketSearchSummaryEvent::dispatch($packetSearch);
    }

    /**
     * Determines if the result contains a packet search result.
     *
     * @param array $res
     * @return bool
     */
    public function isPacketSearchResult(array $res): bool
    {
        if (count($res)>=5) {
            return true;
        }

        return false;
    }

    /**
     * Determines if the result contains a Hot report result.
     *
     * @param array $res
     * @return bool
     */
    public function isHotReportLine(array $res): bool
    {
        if (count($res)>=3) {
            return true;
        }

        return false;
    }

    /**
     * Determines if the result contains a search result Summary.
     *
     * @param array $res
     * @return bool
     */
    public function isSearchSummary(array $res): bool
    {
        if (count($res)>=3) {
            return true;
        }

        return false;
    }

    /**
     * Determines if the result contains a HotReport Summary.
     *
     * @param array $res
     * @return bool
     */
    public function isHotReportSummary(array $res): bool
    {
        if (count($res)>=3) {
            return true;
        }

        return false;
    }

    /**
     * Extracts The Summary of a search
     * $packet[0] contains the entire matched string
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
    public function extractPacketSearchResult(string $txt): array
    {
        $packet = [];
        preg_match(self::REQUEST_INSTRUCTIONS_MASK, $txt, $packet);
        return $packet;
    }

    /**
     * Extracts The Summary of a search
     * $result[0] contains the entire matched string
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
    public function extractHotReportLine(string $txt): array
    {
        $result = [];
        preg_match(self::HOT_REPORT_RESULT, $txt, $result);
        return $result;
    }

    /**
     * Extracts any search summary from the text
     * $summary[0] contants the entire matched string
     * $summary[1] is the chatroom.
     * $summary[2] is the number of results.
     *
     * If less than 3 elements are returned, it is not a valid summary.
     *
     * @param string $txt
     * @return array
     */
    public function extractSearchSummary(string $txt): array
    {
        $summary = [];
        preg_match(self::SEARCH_SUMMARY_MASK, $txt, $summary);
        return $summary;
    }

    /**
     * Extracts any Hot Report summary from the text
     * $summary[0] contants the entire matched string
     * $summary[1] Channel Name string.
     * $summary[2] Summary string.
     *
     * If less than 3 elements are returned, it is not a valid summary.
     *
     * @param string $txt
     * @return array
     */
    public function extractHotReportSummary(string $txt): array
    {
        $summary = [];
        preg_match(self::HOT_REPORT_SUMMARY_MASK, $txt, $summary);
        return $summary;
    }

    /**
     * Determines if the message concerns a DCC queue.
     *
     * @param string $txt
     * @return bool
     */
    public function isQueuedNotification(string $txt): bool
    {
        if ('Queued' === substr(trim($txt), 0, 6)) {
            return true;
        }

        return false;
    }

    /**
     * Determines if the message concerns a DCC queue.
     *
     * @param string $txt
     * @return bool
     */
    public function isQueuedResponse(string $txt): bool
    {
        $matches = [];
        if (preg_match(self::QUEUED_RESPONSE_MASK, $txt, $matches)) {
            if (isset($matches[3])) {
                return true;
            }
        };

        return false;
    }

    /**
     * Locates or creates a packet object based on the input criteria.
     *
     * @param string $fileName
     * @param string $fileSize
     * @param string $botName
     * @param string $packetNumber
     * @return Packet
     */
    public function resolvePacket(string $fileName, string $fileSize, string $botName, string $packetNumber): Packet
    {
        $bot = Bot::firstOrNew([
            'nick' => $botName,
            'network_id' => $this->network->id,
        ]);
        $channel = $this->getBotChannelByBestGuess($bot);

        $mediaTypeGuesser = new MediaTypeGuesser($fileName);
        $mediaType = $mediaTypeGuesser->guess();

        $packet = Packet::updateOrCreate(
            ['number' => $packetNumber, 'network_id' => $this->network->id, 'channel_id' => $channel->id, 'bot_id' => $bot->id],
            ['file_name' => $fileName, 'size' => $fileSize, 'media_type' => $mediaType]
        );

        return $packet;
    }


    /**
     * Makes a best guess at which channel a Bot may represent in the absence of a channel name.
     *
     * @param Bot $bot
     * @return Channel
     */
    public function getBotChannelByBestGuess(Bot $bot): Channel
    {
        // Use the same channel of a packet that was last reported by this bot.
        $packet = Packet::where('bot_id', $bot->id)->orderBy('id', 'DESC')->first();

        if (null !== $packet) {
            return $packet->channel;
        }

        // If this bot has not reported any packets, just pick the last channel reported on this network.
        $packet = Packet::where('network_id', $this->network->id)->orderBy('id', 'DESC')->first();

        if (null !== $packet) {
            return $packet->channel;
        }

        // If still nothing qualifies just pick a channel on this network.
        $channel = Channel::where('network_id', $this->network->id)->first();

        return $channel;
    }

    /**
     * Parses the response that a download was queued.
     *
     * @param string $txt
     * @return Packet|null
     */
    public function markAsQeueued(string $txt): Packet|null
    {
        $var = env('VAR', '/usr/var');
        $downloadDir = "$var/download";

        [$packetNumber, $file, $position] = $this->extractQueuedResponse($txt);

        $packet = Packet::where('number', $packetNumber)->where('file_name', $file)->orderByDesc('created_at')->first();

        if ($packet) {
            Download::updateOrCreate(
                [ 'file_uri' => "$downloadDir/$file", 'packet_id' => $packet->id ],
                [
                    'status'        => Download::STATUS_QUEUED,
                    'file_name'     => $packet->file_name,
                    'queued_status' => $position,
                    'meta'          => $packet->meta,
                    'media_type'    => $packet->media_type
                ]
            );

            if (!$this->isFileDownloadLocked($file)) {
                $this->lockFile($file);
                //Queue the job that checks if the file is finished downloading.
                $timeStamp = new DateTime('now');
                CheckFileDownloadCompleted::dispatch($file, $timeStamp)
                    ->delay(now()->addMinutes(CheckFileDownloadCompleted::SCHEDULE_INTERVAL));
            }
        }

        return $packet;

    }

    /**
     * Parses the DCC queue and makes updated notation to the download state.
     *
     * @param string $txt
     * @return void
     */
    public function doQueuedStateChange(string $txt): void
    {
        $var = env('VAR', '/usr/var');
        $downloadDir = "$var/download";
        [$file, $position, $total] = $this->extractQueuedState($txt);

        $download = Download::where('file_uri', "$downloadDir/$file")
                            ->orderByDesc('created_at')
                            ->first();

        // Update the queued state if the download is not marked as complete.
        if ($download && $download->status !== Download::STATUS_COMPLETED) {
            $download->queued_status = $position;
            $download->queued_total = $total;
            $download->save();

            if (!$this->isFileDownloadLocked($file)) {
                $this->lockFile($file);
                //Queue the job that checks if the file is finished downloading.
                $timeStamp = new DateTime('now');
                CheckFileDownloadCompleted::dispatch($file, $timeStamp)
                    ->delay(now()->addMinutes(CheckFileDownloadCompleted::SCHEDULE_INTERVAL));
            }
        }
    }

    /**
     * Extracts the $file, $position, $total values from the line.
     *
     * @param string $txt
     * @return [$file, $position, $total]
     */
    public function extractQueuedState(string $txt): array
    {
        $matches = [];
        $file = null;
        $position = null;
        $total = null;

        if (preg_match(self::QUEUED_MASK, $txt, $matches)) {
            if (isset($matches[1])) {
                $file = $matches[1];
            }

            if (isset($matches[2])) {
                $position = $matches[2];
            }

            if (isset($matches[3])) {
                $total = $matches[3];
            }
        };

        return [$file, $position, $total];
    }

    /**
     * Extracts the $packetId, $file, $position values from the line.
     *
     * @param string $txt
     * @return [$packetNum, $file, $position]
     */
    public function extractQueuedResponse(string $txt): array
    {
        $matches = [];
        $packetNum = null;
        $file = null;
        $position = null;

        if (preg_match(self::QUEUED_RESPONSE_MASK, $txt, $matches)) {
            if (isset($matches[1])) {
                $packetNum = trim($matches[1]);
            }

            if (isset($matches[2])) {
                $file = $matches[2];
            }

            if (isset($matches[3])) {
                $position = $matches[3];
            }
        };

        return [$packetNum, $file, $position];
    }

    /**
     * Handles name events.
     *
     * @return void
     */
    public function namesHandler(): void
    {
        $this->client->on('names', function (IrcChannel $channel, array $names) {
            $channelName = $channel->getName();
            $this->updateChannel($channelName);
        });

        foreach($this->client->getChannels() as $channel) {
            $channelName = $channel->getName();

            $this->client->on("names$channelName", function (array $names) use ($channelName) {
                $this->updateChannel($channelName);
            });
        }
    }

    /**
     * Removes all Non-numeric characters from a string.
     *
     * @param string $txtStr
     * @return string
     */
    public function clnNumericStr(string $txtStr): string
    {
        return preg_replace("/[^0-9]/", "", $txtStr);
    }

    /**
     * Connect's to the Server and initializes event listening.
     *
     * @return void
     */
    public function connect(): void
    {
        $this->client->connect();
    }

    /**
     * Procedure for when the connection to a network has terminated.
     *
     * @return void
     */
    protected function terminateInstance(): void
    {
        $message = "connection to: {$this->network->name} terminated.";
        $this->instance->is_connected = false;
        $this->instance->save();
        $this->logDiverter->log(LogMapper::EVENT_CLOSE, $message);
        $this->console->error("****************************************************************************************");
        $this->console->error("           ================[  $message  ]================");
        $this->console->error("****************************************************************************************");
    }

    /**
     * Returns a channel model object with the parameter of the channel name.
     *
     * @param string $name
     * @return Channel|null
     */
    protected function getChannelFromName(string $name): Channel|null
    {
        if (!isset($this->channels[$name])) {
            $channel = Channel::where('name', $name)->first();
            if (null === $channel) return null;
            $this->channels[$name] = $channel;
        }

        return $this->channels[$name];
    }

    /**
     * Get only channels that are parents.
     *
     * @param NetWork $network
     * @return Collection
     */
    protected function getAllParentChannelsForNetwork(NetWork $network): Collection
    {
        return Channel::where('channel_id', null)
            ->where('network_id', $network->id)
            ->where('enabled', true)
            ->get();
    }

    protected function dynamicWordSpacing($word, $totalSpaces)
    {
        $spacer = '';
        $wordLength = strlen($word);
        $numSpaces = $totalSpaces - $wordLength;
        for ($i = 0; $i <= $numSpaces; $i++) {
            $spacer = $spacer.' ';
        }

        return $spacer;
    }

    /**
     * Checks to see if there is a download lock on the file name.
     * Download locks prevents a file from being simultanously downloaded from multiple sources.
     *
     * @return boolean
     */
    protected function isFileDownloadLocked(string $fileName): bool
    {
        $lock = FileDownloadLock::where('file_name', $fileName)->first();

        if (null !== $lock) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Locks a file.
     *
     * @return boolean
     */
    protected function lockFile(string $file): void
    {
        // Lock the file for Downloading to prevent further downloads of the same file.
        FileDownloadLock::create(['file_name' => $file]);
    }


    /**
     * Updates a IrcChannel|string channel variable.
     * Could be null, could be string, could be IrcChannel
     * Fun, Fun, Fun...
     *
     * @param IrcChannel|string $channel
     * @param bool $updateClient
     * @return IrcChannel
     */
    protected function updateChannel(IrcChannel|string $channelName, $updateClient = true): IrcChannel
    {
        $ircChannel = $this->getChannelFromClient($channelName);
        $meta = $ircChannel->toArray();
        $userCount = count($meta['users']);
        $topic = mb_convert_encoding($meta['topic'], self::SUPPORTED_ENCODING);
        $name = $meta['name'];

        if (null !== $topic && (0 < $userCount)) {
            $this->channels[$name] = Channel::updateOrCreate(
                ['name' => $name],
                ['topic' => $topic, 'users' => $userCount, 'meta' => $meta]
            );
        }

        if ($updateClient) {
            $this->updateClient();
        }

        return $ircChannel;
    }

    /**
     * Updates the data representation of the IRC client.
     *
     * @return void
     */
    protected function updateClient(): void
    {
        $meta = $this->client->toArray();
        $isConnected = false;
        if (isset($meta['connection']) && isset($meta['connection']['is_connected'])) {
            $isConnected = $meta['connection']['is_connected'];
        }

        $this->clientModel->meta = $meta;
        $this->clientModel->instance->is_connected = $isConnected;
        $this->clientModel->save();
    }

    /**
     * An IrcChannel instance provided by a handler is not the same instance the client is holding.
     * This takes an IrcInstance or channel name string and gets the instance from the client.
     */
    protected function getChannelFromClient(IrcChannel|string $channelName): IrcChannel
    {
        if ('string' !== gettype($channelName)) {
            $channelName = $channelName->getName();
        }

        return $this->client->getChannel($channelName);
    }

}
