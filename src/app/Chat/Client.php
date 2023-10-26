<?php

namespace App\Chat;

use Illuminate\Console\Command;

use Jerodev\PhpIrcClient\IrcClient,
    Jerodev\PhpIrcClient\IrcChannel,
    Jerodev\PhpIrcClient\Options\ClientOptions;

use App\Chat\LogDiverter,
    App\Models\Client as ClientModel,
    App\Models\Instance,
    App\Models\Nick,
    App\Models\Network,
    App\Models\Channel;

use Illuminate\Database\Eloquent\Collection;

use function Ramsey\Uuid\v1;

class Client 
{

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

    protected function registerInstance()
    {
        $logUri = $this->getInstanceLogUri();
        $pid = ($pid = getmypid()) ? $pid : null;

        $this->instance =  Instance::updateOrCreate(
            ['client_id' => $this->getClientId()],
            ['status' => Instance::STATUS_UP, 'log_uri' => $logUri, 'pid' => $pid]
        );

        $this->operationManager = new OperationManager($this->client, $this->instance);
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
            $this->console->error("$user has been kicked from $channelName by $kicker. reason:\n$message\n\n");

            # Update the Channel Metadata
            $this->channelUpdater->update($channel);
        });
    }

    /**
     * Handles a dcc event.
     *
     * @return void
     */
    public function DccHandler(): void
    {
        $this->client->on('dcc', function($action, $fileName, $ip, $port, $fileSize) {
            
            $this->console->warn("A DCC event has been sent, with the following information:\n\n");
            $this->console->warn("action $action, fileName: $fileName, ip: $ip, port: $port, fileSize: $fileSize\n\n");
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
            # Divert message to the log for this instance.
            $this->logDiverter->log("$userName to: $target: $message");
            $this->console->warn("$userName to $target says: $message");
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
                $this->console->warn("$from says: $message");
            }

            $line .= " @$from: $message";

            # Divert message to the log for this instance.
            $this->logDiverter->log($line);

            # Do any pending operations.
            $this->operationManager->doOperations();
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
    
            $this->console->warn(" ========[  $txt ");
        });
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
