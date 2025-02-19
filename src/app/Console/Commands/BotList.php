<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Exceptions\UnknownBotException,
    App\Jobs\BotListRequest,
    App\Models\Bot,
    App\Models\Network;

class BotList extends Command
{
    /** The bot from which we will request a list. */
    protected ?Bot $bot = null;

    /** The network from which we can connect to an instance. */
    protected ?Network $network = null;

    /** The name and signature of the console command. */
    protected $signature = 'mcol:bot-list {network} {nick}';

    /** The console command description. */
    protected $description = 'Queue downloading of a list of packets from a bot';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $bot = $this->getBot();
        BotListRequest::dispatch($bot);
        $this->warn("Requested packet list from {$bot->nick}@{$bot->network->name}");
    }

    /**
     * Get the bot instance based on the provided nickname and network.
     *
     * @throws UnknownBotException If the bot is not found.
     */
    public function getBot(): Bot
    {
        return $this->bot ??= Bot::where('nick', $this->argument('nick'))
            ->where('network_id', $this->getNetwork()->id)
            ->firstOr(fn () => throw new UnknownBotException("Bot with nick: {$this->argument('nick')} was not found on {$this->getNetwork()->name}."));
    }

    /**
     * Get the network instance based on the provided name.
     */
    public function getNetwork(): Network
    {
        return $this->network ??= Network::where('name', $this->argument('network'))->firstOrFail();
    }
}
