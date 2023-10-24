<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Channel,
    App\Models\Network;

class ChannelSeeder extends Seeder
{
    protected Array $channels = [
        'Abjects' => [
            'moviegods' => [
                'topic' => '!donate or !donate_de - BoxOwners/Rooters/Donations Needed  #MOVIEGODS - Only channel supporting SSL XDCC -//- If you are new to this type !help -//- Join #MG-Lounge for requests/questions/tv-subs/spam free chat -//- Join #mg-chat', 
                'users' => 1395,
            ],
            'beast-xdcc' => [
                'topic' => ' .:||:. BEAST-XDCC \\\\ FAST speeds, SCENE releases //// NEVER throttled \\\\  join #BEAST-CHAT   ::: http://ixirc.com/?cid=218 NO THROTTLES | NO LOGGING | NO HOMO  :::  BEAST-XDCC ',
                'users' => 336
            ]
        ],
    ];

    protected Array $children = [
        'moviegods' => [
            'mg-chat' => [
                'topic' => '!donate or !donate_de - BoxOwners/Rooters/Donations Needed  #MOVIEGODS - Only channel supporting SSL XDCC --//- If you are new to this type !help -//- Join #MG-Lounge for requests/questions/comments/tv-subs/spam free chat -//- Join #MG-Help for help', 
                'users' => 1369,
            ],
        ],
        'beast-xdcc' => [
            'BEAST-CHAT' => [
                'topic' => '', 
                'users' => 335,
            ],
        ],
    ];
    
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->getChannels() as $networkName => $channels) {
            $network = $this->getNetworkByName($networkName);

            if (NULL === $network) continue;

            foreach ($channels as $name => $c) {

                $channel = $this->getChannelByName($name);

                if (NULL !== $channel) continue;
            
                $c['network_id'] = $network->id;
                $c['name'] = $name;
                $channel = Channel::factory()->create($c);

                if (isset($this->children[$name])) {
                    foreach ($this->children[$name] as $childName => $child) {

                        $childChannel = $this->getChannelByName($childName);

                        if (NULL !== $childChannel) continue;

                        $child['network_id'] = $channel->network_id;
                        $child['name'] = $childName;
                        $child['channel_id'] = $channel->id;
                        Channel::factory()->create($child);
                    }
                }
            }
            
        }
    }

    /**
     * Get the value of networks
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
     * Get an instance of Channel by name.
     */ 
    public function getChannelByName(string $name): Channel|null
    {
        return Channel::where('name', $name)->first();
    }
}
