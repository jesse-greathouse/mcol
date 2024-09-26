<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Client,
    App\Models\Channel,
    App\Models\Instance,
    App\Models\Network,
    App\Models\Nick;

class InstanceSeeder extends Seeder
{
        
    /**
     * List of names for nicks
     *
     * @var Array
     */
    protected Array $clients = [
        [ 
            'network' => 'Abjects',
            'nick' => 'SweattyPickle_458'
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->getClients() as $clientParams) {
            $client = $this->getClientWithParams($clientParams);

            if (null === $client) continue;

            Instance::updateOrCreate(
                ['client_id' => $client->id],
                ['log_uri' => $this->getLogUriForClient($client)]
            );
        }
    }

    /**
     * Returns a client with the given params.
     *
     * @param array $clientParams
     * @return Client
     */
    public function getClientWithParams(array $clientParams): Client|null
    {
        $network = $this->getNetworkByName($clientParams['network']);
        if (null === $network) return null;

        $nick = $this->getNickByName($clientParams['nick']);
        if (null === $nick) return null;

        $client = Client::updateOrCreate(
            ['network_id' => $network->id, 'nick_id' => $nick->id],
            ['enabled' => 1]
        );

        return $client;
    }

    /**
     * Get an instance of Network by name.
     */ 
    public function getNetworkByName(string $name): Network|null
    {
        return Network::where('name', $name)->first();
    }

    /**
     * Get an instance of Nick by name.
     */ 
    public function getNickByName(string $name): Nick|null
    {
        return Nick::where('nick', $name)->first();
    }

    /**
     * Get list of names for nicks
     *
     * @return  Array
     */ 
    public function getClients(): array
    {
        return $this->clients;
    }

    public function getLogUriForClient(Client $client): string
    {
        $logDir = env('LOG_DIR', null);
        return sprintf("%s/instances/%s/%s.log",
            $logDir,
            $client->nick->nick,
            $client->network->name
        );
    }
}
