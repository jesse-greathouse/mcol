<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Exceptions\UnknownBotException,
    App\Jobs\BotListRequest,
    App\Models\Bot,
    App\Models\Network;

class Botlist extends Command
{
    /**
     * 
     * Bot from which we will request a list.
     *
     * @var Bot
     */
    protected $bot;

    /**
     * 
     * Network from which we can connect to an instance.
     *
     * @var Network
     */
    protected $network;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:bot-list {network} {nick}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue downloading of a list of packets from a bot';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bot = $this->getBot();
        BotListRequest::dispatch($bot);
        $this->warn("Requested packet list from {$bot->nick}@{$bot->network->name}");
    }


    public function getBot(): Bot|null
    {
        if (null === $this->bot) {
            $nick = $this->argument('nick');
            $network = $this->getNetwork();
            $bot = Bot::where('nick', $nick)
                ->where('network_id', $network->id)
                ->first();

            if (null === $bot) {
               throw new UnknownBotException("Bot with nick: $nick was not found on {$network->name}.");
            }

            $this->bot = $bot;
        }

        return $this->bot;
    }

    public function getNetwork(): Network|null
    {
        if (null === $this->network) {
            $networkName = $this->argument('network');
            $n = Network::where('name', $networkName)->first();
            if (!$n) {
                return null;
            }

            $this->network = $n;
        }

        return $this->network;
    }
}
