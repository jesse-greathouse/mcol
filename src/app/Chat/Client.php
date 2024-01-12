<?php

namespace App\Chat;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

use Jerodev\PhpIrcClient\IrcClient,
    Jerodev\PhpIrcClient\IrcChannel,
    Jerodev\PhpIrcClient\Options\ClientOptions;

use App\Chat\LogDiverter,
    App\Models\Client as ClientModel,
    App\Models\Download,
    App\Models\Instance,
    App\Models\Nick,
    App\Models\Network,
    App\Models\Packet,
    App\Models\Channel;

use Illuminate\Database\Eloquent\Collection;

class Client 
{
    const QUEUED_MASK = '/^Queued \d+h\d+m for \"(.+)\", in position (\d+) of (\d+)\. .+$/';
    const QUEUED_RESPONSE_MASK = '/pack ([0-9]+) \(\"(.+)\"\) in position ([0-9]+)\./';

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
        $this->client->on('kick', function(IrcChannel $channel, string $user, string $kicker, $message) {
            $channelName = $channel->getName();
            $this->console->error("$user has been kicked from $channelName by $kicker. Reason:$message");

            # Update the Channel Metadata
            $this->channelUpdater->update($channel);
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
            $dir = env('DIR', '/usr');
            $src = env('SRC', '/usr/src');
            $bin = "$dir/bin";
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
                        // $cmd = "PRIVMSG $userName :XDCC RESUME $fileName $portCln $position";
                        // $this->client->send($cmd);
                        $positionCln = $this->clnNumericStr($position);

                        $this->console->warn("RESUMING DCC Client: $bin/php artisan mcol:make-dcc --host=$ipCln --port=$portCln --file=$fileName --file-size=$positionCln  --bot='$userName' --resume=$positionCln");

                        Process::path($src)->start("$bin/php $src/artisan mcol:make-dcc --host=$ipCln --port=$portCln --file=$fileName --file-size=$positionCln  --bot='$userName' --resume=$positionCln", function (string $type, string $output) {
                            $this->console->info("Command $type output: $output");
                        });
                    } else {
                        unlink($uri);
                    }
                } else {
                    $this->console->warn("RUNNING DCC Client: $bin/php artisan mcol:make-dcc --host=$ipCln --port=$portCln --file=$fileName --file-size=$fileSizeCln --bot='$userName'");

                    Process::path($src)->start("$bin/php artisan mcol:make-dcc --host=$ipCln --port=$portCln --file=$fileName --file-size=$fileSizeCln --bot='$userName'", function (string $type, string $output) {
                        $this->console->info("Command $type output: $output");
                    });
                }
            }

            if (false !== strpos($message, 'DCC ACCEPT')) {
                [, , $fileName, $ip, $port, $position] = explode(' ', $message);
                $positionCln = $this->clnNumericStr($position);
                $ipCln = $this->clnNumericStr($ip);
                $portCln = $this->clnNumericStr($port);
                $uri = "$downloadDir/$fileName";

                $this->console->warn($message);
                $this->console->warn("RESUMING DCC Client: $bin/php artisan mcol:make-dcc --host=$ipCln --port=$portCln --file=$fileName --file-size=$positionCln  --bot='$userName' --resume=$positionCln");

                Process::path($src)->start("$bin/php $src/artisan mcol:make-dcc --host=$ipCln --port=$portCln --file=$fileName --file-size=$positionCln  --bot='$userName' --resume=$positionCln", function (string $type, string $output) {
                    $this->console->info("Command $type output: $output");
                });
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
            } else if ($this->isQueuedResponse($txt)) {
                $packet = $this->markAsQeueued($txt);
                if ($packet) {
                    $this->console->warn(" ========[  Queued for #{$packet->number} {$packet->file_name} - {$packet->size}");
                }
                return;
            }
    
            $this->console->warn(" ========[  $txt ");
        });
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

        if ($download) {
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
     * @return Channel
     */
    protected function getChannelFromName(string $name): Channel
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

}
