<?php

namespace Database\Seeders;

use App\Models\Network;
use App\Models\Server;
use Illuminate\Database\Seeder;

class ServerSeeder extends Seeder
{
    protected array $servers = [
        'Abjects' => 'irc.abjects.net',
        'Rizon' => 'irc.rizon.net',
    ];

    protected array $networks = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->getServers() as $networkName => $hostName) {
            $network = $this->getNetworkByName($networkName);

            if (! $network) {
                continue;
            }

            Server::updateOrCreate(
                ['host' => $hostName], ['network_id' => $network->id]
            );
        }
    }

    /**
     * Get $servers
     */
    public function getServers(): array
    {
        return $this->servers;
    }

    /**
     * Get an instance of Network by name.
     */
    public function getNetworkByName(string $name): ?Network
    {
        return Network::where('name', $name)->first();
    }
}
