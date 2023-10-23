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
    protected Array $channels = [
        'Abjects' => [
            'name' => 'moviegods',
            'nick'  => 'MediaEnjoyer_201',
            'enabled'  => 1,
        ]
    ];



    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->getNetworks() as $networkName) {
            $network = $this->getNetworkByName($networkName);

            if (isset($this->channels[$network->name])) {
                ['name' => $name, 'nick' => $nick, 'enabled' => $enabled] = $this->channels[$network->name];

                $channel = $this->getChannelByName($name);
                if (null === $channel) continue;

                $n = $this->getNickByName($nick);
                if (null === $n) continue;

                Client::updateOrCreate(
                    ['network_id' => $network->id, 'channel_id' => $channel->id, 'nick_id' => $n->id],
                    ['enabled' => $enabled]
                );
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
     * Get $networks
     *
     * @return  Array
     */ 
    public function getChannels(): array
    {
        return $this->channels;
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
