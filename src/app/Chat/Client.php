<?php

namespace App\Chat;

use Illuminate\Console\Command;

use Jerodev\PhpIrcClient\IrcClient,
    Jerodev\PhpIrcClient\IrcChannel,
    Jerodev\PhpIrcClient\Options\ClientOptions;

use App\Chat\LogDiverter,
    App\Events\HotReportLine as HotReportLineEvent,
    App\Events\HotReportSummary as HotReportSummaryEvent,
    App\Events\PacketSearchResult as PacketSearchResultEvent,
    App\Events\PacketSearchSummary as PacketSearchSummaryEvent,
    App\Jobs\DccDownload,
    App\Models\Bot,
    App\Models\Channel,
    App\Models\Client as ClientModel,
    App\Models\Download,
    App\Models\HotReport,
    App\Models\HotReportLine,
    App\Models\Instance,
    App\Models\Nick,
    App\Models\Network,
    App\Models\Packet,
    App\Models\PacketSearch,
    App\Models\PacketSearchResult;

use Illuminate\Database\Eloquent\Collection;

class Client 
{
    const LINE_COLUMN_SPACES = 50;
    const QUEUED_MASK = '/^Queued \d+h\d+m for \"(.+)\", in position (\d+) of (\d+)\. .+$/';
    const QUEUED_RESPONSE_MASK = '/pack ([0-9]+) \(\"(.+)\"\) in position ([0-9]+)\./';
    const REQUEST_INSTRUCTIONS_MASK = '/\|10\s(.*)04\s\|10\s(.*)04\s\|09\s\/msg\s(.*)\sXDCC\sSEND\s([0-9].*)\s04.*/';
    const HOT_REPORT_RESULT = '/(\d\.\d)\s0\d\s([A-Za-z0-9_\.\-]+)\s+(\d\.\d)\s\d\s([A-Za-z0-9_\.\-]+)/';
    const SEARCH_SUMMARY_MASK = '/(\#[A-Za-z].*)\s\-\sFound\s([0-9].*)\sONLINE Packs/';
    const HOT_REPORT_SUMMARY_MASK = '/\d\d(\#[A-Za-z0-9]+)\s+(.*)$/';

    /**
     * Client Id
     *
     * @var Int
     */
    protected $clientId;

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
     * ChannelUpdater instance for this client.
     *
     * @var ChannelUpdater
     */
    protected $channelUpdater;

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
        $this->nick = $nick;
        $this->network = $network;
        $this->console = $console;
        $this->channelUpdater = new ChannelUpdater();
        $this->logDiverter = new LogDiverter($this->getInstanceLogUri());

        $options = new ClientOptions($nick->nick);
        $this->client = new IrcClient("{$network->firstServer->host}:6667", $options);
        $this->assignHandlers();
    }

    /**
     * Assigns all handlers for the various events.
     *
     * @return void
     */
    protected function assignHandlers(): void
    {
        $this->noticeHandler();
        $this->joinHandler();
        $this->registeredHandler();
        $this->disconnectHandler();
        $this->pingHandler();
        $this->namesHandler();
        $this->messageHandler();
        $this->privMessageHandler();
        $this->kickHandler();
    }

    /**
     * Opens a new instance.
     *
     * @return void
     */
    protected function registerInstance()
    {
        $logUri = $this->getInstanceLogUri();
        $pid = ($pid = getmypid()) ? $pid : null;

        $this->instance =  Instance::updateOrCreate(
            ['client_id' => $this->getClientId()],
            ['status' => Instance::STATUS_UP, 'log_uri' => $logUri, 'pid' => $pid]
        );

        $this->operationManager = new OperationManager($this->client, $this->instance);
        $this->downloadProgressManager = new DownloadProgressManager($this->instance, $this->console);
    }

    protected function getInstanceLogUri(): string
    {
        $logDir = env('LOG_DIR', '/var/log');

        $instanceLogDir = "$logDir/instances/{$this->nick->nick}";
        if (!file_exists($instanceLogDir)) {
            mkdir($instanceLogDir, 0755, true);
        }

        $logfile = "$instanceLogDir/{$this->network->name}.log";
        touch($logfile);
        return $logfile;
    }

    /**
     * Returns the client id of the currently running client.
     * Null if the client has not been instantiated.
     *
     * @return integer|null
     */
    protected function getClientId(): int|null
    {
        // Returns the id of a Client Model. 
        // Save id to refrain future DBAL client calls in long-running processes.
        if (null === $this->clientId) {
            $client = ClientModel::where('enabled', true)
                        ->where('network_id', $this->network->id)
                        ->where('nick_id', $this->nick->id)->first();

            if (null === $client) {
                return null;
            } else {
                $this->clientId = $client->id;
            }
        }

        return $this->clientId;
    }

    /**
     * Handles a join event.
     *
     * @return void
     */
    public function joinHandler(): void
    {
        $this->client->on('joinInfo', function(string $user, string $channelName) {
            $this->console->info("$user joined $channelName");
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
            $updateChannel = false;

            if (is_a($channel, IrcChannel::class)) {
                $channelName = $channel->getName();
                $updateChannel = true;
            } else {
                $channelName = $channel;
            }

            $this->console->error("$user has been kicked from $channelName by $kicker. Reason:$message");

            if ($updateChannel) {
                # Update the Channel Metadata
                $this->channelUpdater->update($channel);
            }
        });
    }

    /**
     * Handles a dcc event.
     *
     * @return voids
     */
    public function DccHandler(): void
    {
        $this->client->on('dcc', function($action, $fileName, $ip, $port, $fileSize) {
            $this->console->warn("A DCC event has been sent, with the following information:\n\n");
            $this->console->warn("action $action, fileName: $fileName, ip: $ip, port: $port, fileSize: $fileSize\n");
        });
    }

    /**
     * Handles the registered event (connected).
     *
     * @return void
     */
    public function registeredHandler(): void
    {
        $this->client->on('registered', function() {
            $this->console->info('connected');

            $this->registerInstance();

            $channels = $this->getAllParentChannelsForNetwork($this->network);

            foreach($channels as $channel) {
                $this->client->join($channel->name);
                foreach($channel->children as $child) {
                    $this->client->join($child->name);
                }
            }  
        });
    }

    /**
     * Handles the event of when the client disconnects.
     *
     * @return void
     */
    public function disconnectHandler(): void
    {
        $this->client->on('close', function() {
            $this->instance->status = Instance::STATUS_DOWN;
            $this->instance->save();
            $this->console->error('disconnected');
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

            # Divert message to the log for this instance.
            $this->logDiverter->log("$userName to: $target: $message");
            $this->console->warn("$userName to $target says: $message");
            $downloadDir = env('DOWNLOAD_DIR', '/var/download');

            # DCC SEND PROTOCOL
            if (false !== strpos($message, 'DCC SEND')) {
                // $message is a string like: "DCC SEND Frasier.2023.S01E04.1080p.WEB.h264-ETHEL.mkv 1311718603 58707 2073127114" 
                [, , $fileName, $ip, $port, $fileSize] = explode(' ', $message);
                $fileSizeCln = $this->clnNumericStr($fileSize);
                $ipCln = $this->clnNumericStr($ip);
                $portCln = $this->clnNumericStr($port);
                $uri = "$downloadDir/$fileName";

                if (file_exists($uri)) {
                    $position = filesize($uri);
                    if (false !== $position) {
                        $positionCln = $this->clnNumericStr($position);
                        DccDownload::dispatch($ipCln, $portCln, $fileName, $positionCln, $userName, $positionCln)->onQueue('download');
                        $this->console->warn("Queued to resume DCC Download Job: host: $ipCln port: $portCln file: $fileName file-size: $positionCln bot: '$userName' resume: $positionCln");
                    } else {
                        unlink($uri);
                    }
                } else {
                    DccDownload::dispatch($ipCln, $portCln, $fileName, $fileSizeCln, $userName)->onQueue('download');
                    $this->console->warn("Queued DCC Download Job: host: $ipCln port: $portCln file: $fileName file-size: $fileSizeCln bot: '$userName'");
                }

                return;
            }

            if (false !== strpos($message, 'DCC ACCEPT')) {
                [, , $fileName, $ip, $port, $position] = explode(' ', $message);
                $positionCln = $this->clnNumericStr($position);
                $ipCln = $this->clnNumericStr($ip);
                $portCln = $this->clnNumericStr($port);
                $uri = "$downloadDir/$fileName";
                $this->console->warn($message);
                DccDownload::dispatch($ipCln, $portCln, $fileName, $positionCln, $userName, $positionCln)->onQueue('download');
                $this->console->warn("Queued to resume DCC Download Job: host: $ipCln port: $portCln file: $fileName file-size: $positionCln bot: '$userName' resume: $positionCln");
                return;
            }
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
                # Update the Channel Metadata.
                $this->channelUpdater->update($channel);
                $line .= $channel->getName();
            } else {
                return;
            }

            $line .= " @$from: $message";

            # Divert message to the log for this instance.
            $this->logDiverter->log($line);

            # Do any pending operations.
            $this->operationManager->doOperations();

            # Report download progress at an interval.
            $this->downloadProgressManager->reportProgress();
        });

        $this->client->on("message{$this->nick->nick}", function (string $from, IrcChannel $channel = null, string $message) {
            $line = '';

            if (null !== $channel) {
                # Update the Channel Metadata.
                $this->channelUpdater->update($channel);
                $line .= $channel->getName();
            }

            $line .= " @$from: $message";

            $this->console->warn($line);

            # Divert message to the log for this instance.
            $this->logDiverter->log($line);
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
            $clean  = PacketLocator::cleanMessage($notice);
            $parts = explode(' ', $clean);
            $subject = array_shift($parts);
            $txt = implode(' ', $parts);

            if ($subject !== $this->nick->nick) {
                $txt = "$subject $txt";
            }

            if ($this->isQueuedNotification($txt)) {
                $this->doQueuedStateChange($txt);
                $this->console->warn(" ========[  $txt ");
                return;
            } else if ($this->isQueuedResponse($txt)) {
                $packet = $this->markAsQeueued($txt);
                if ($packet) {
                    $this->console->warn(" ========[  Queued for #{$packet->number} {$packet->file_name} - {$packet->size}");
                }
                return;
            }

            ## Check to see if this is a search result.
            $packetSearchResult = $this->extractPacketSearchResult($txt);
            if ($this->isPacketSearchResult($packetSearchResult)) {
                [, $fileSize, $fileName, $botName, $packetNumber] = $packetSearchResult;
                $this->console->warn(" ==[  > $botName   $packetNumber - $fileSize $fileName");
                $this->searchResultHandler($fileName, $fileSize, $botName, $packetNumber);
                return;
            }

            ## Check to see if this is a search summary.
            $searchSummary = $this->extractSearchSummary($txt);
            if ($this->isSearchSummary($searchSummary)) {
                [, $channelName, $numberResults] = $searchSummary;
                $this->console->warn(" ========[  Found $numberResults results in $channelName");
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
                    $this->console->warn(" ==[    [$hotReportRank1] $hotReportFileName1 $spacer [$hotReportRank2] $hotReportFileName2");
                 } else {
                    $this->console->warn(" ==[    [$hotReportRank1] $hotReportFileName1");
                 }

                 return;
             }

            ## Check to see if this is a Hot Report summary.
            $hotReportSummary = $this->extractHotReportSummary($txt);
            if ($this->isHotReportSummary($hotReportSummary)) {
                [, $channelName, $hotReportSummaryStr] = $hotReportSummary;
                $channelNameSanitized = strtolower($channelName);
                $this->console->warn(" ========[  $channelNameSanitized $hotReportSummaryStr");
                $this->hotReportSummaryHandler($channelNameSanitized, $hotReportSummaryStr);
                return;
            }
    
            $this->console->warn(" ========[  $txt ");
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

        $packet = Packet::updateOrCreate(
            ['number' => $packetNumber, 'network_id' => $this->network->id, 'channel_id' => $channel->id, 'bot_id' => $bot->id],
            ['file_name' => $fileName, 'size' => $fileSize]
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
                [ 'status' => Download::STATUS_QUEUED, 'queued_status' => $position ]
            );
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
     * Handles Ping events.
     *
     * @return void
     */
    public function pingHandler(): void
    {
        $this->client->on('ping', function() {
            $this->console->info('ping');
        });
    }

    /**
     * Handles name events.
     *
     * @return void
     */
    public function namesHandler(): void
    {
        $this->client->on("names", function (IrcChannel $channel) {
            $userList = $channel->getUsers();
            if (count($userList) > 0) {
                $rows = [];
                foreach($userList as $user) {
                    $rows[] = [$user];
                }
    
                $this->console->table(['Nick'], $rows);
            }
        });
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
     * Returns a channel model object with the parameter of the channel name.
     *
     * @param string $name
     * @return Channel|null
     */
    protected function getChannelFromName(string $name): Channel|null
    {
        return Channel::where('name', $name)->first();
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
            ->get();
    }

    private function dynamicWordSpacing($word, $totalSpaces)
    {
        $spacer = '';
        $wordLength = strlen($word);
        $numSpaces = $totalSpaces - $wordLength;
        for ($i = 0; $i <= $numSpaces; $i++) {
            $spacer = $spacer.' ';
        }

        return $spacer;
    }

}
