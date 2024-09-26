<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Client,
    App\Models\Channel,
    App\Models\Network,
    App\Models\Nick;

class ClientSeeder extends Seeder
{
    /**
     * @var Array $networks
     */
    protected Array $networks = [
        'Abjects',
    ];

    /**
     * @var Array $networks
     */
    protected Array $clients = [
        'Abjects' => ['SweattyPickle_458'],
    ];



    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->getNetworks() as $networkName) {
            $network = $this->getNetworkByName($networkName);

            if (isset($this->clients[$network->name])) {
                foreach($this->clients[$network->name] as $name) {

                    $nick = $this->getNickByName($name);
                    if (null === $nick) continue;

                    Client::updateOrCreate(
                        ['network_id' => $network->id, 'nick_id' => $nick->id],
                        ['enabled' => true]
                    );
                }
            }
        }
    }

    /**
     * Get $networks
     *
     * @return  Array
     */ 
    public function getNetworks(): array
    {
        return $this->networks;
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
     * Get an instance of Channel by name.
     */ 
    public function getChannelByName(string $name): Channel|null
    {
        return Channel::where('name', $name)->first();
    }
}
