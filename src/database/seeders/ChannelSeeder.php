<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Channel,
    App\Models\Network;

class ChannelSeeder extends Seeder
{
    protected Array $networks = [
        'Abjects' => [
            '#moviegods' => [
                'topic' => '',
                'users' => 0,
                'meta' => [],
                'children' => [
                    '#mg-chat' => [
                        'topic' => '',
                        'users' => 0,
                        'meta' => [],
                    ],
                ]
            ],
        ],
        'Rizon' => [
            '#ELITEWAREZ' => [
                'topic' => '',
                'users' => 0,
                'meta' => [],
                'children' => [
                    '#elite-chat' => [
                        'topic' => '',
                        'users' => 0,
                        'meta' => [],
                    ],
                ]
            ],
        ],
    ];


    /**
     * Run the database seeds.
     *
     * Parent Channels are primary channels that theoretically have bots that are offering file sharing.
     * Channel operators sometimes require users to join associated channels, so we call these the "child" channels.
     *
     */
    public function run(): void
    {
        foreach ($this->networks as $name => $parents) {
            $network = $this->getNetworkOrGenerate($name);

            foreach($parents as $parentName => $parentData) {
                $parent = $this->getChannelForNetworkOrGenerate($network, $parentName, $parentData);
                foreach($parentData['children'] as $childName => $childData) {
                    $this->getChannelForNetworkOrGenerate($network, $childName, $childData, $parent->id);
                }
            }
        }
    }

    /**
     * With a Network object, find a channel by the channel name.
     * Create it if it does not exist.
     *
     * @param Network $network
     * @param string $channelName
     * @param array $channelData
     * @param ?int|null $parentId
     * @return Channel
     */
    public function getChannelForNetworkOrGenerate(Network $network, string $channelName, array $channelData, ?int $parentId = null): Channel
    {
        $channel = Channel::where('network_id', $network->id)->where('name', $channelName)->first();
        if (null !== $channel) return $channel;

        return Channel::factory()->create([
            'network_id'    => $network->id,
            'name'          => $channelName,
            'topic'         => $channelData['topic'],
            'users'         => $channelData['users'],
            'meta'          => $channelData['meta'],
            'channel_id'    => $parentId,
        ]);
    }

    /**
     * With a Network name string, retrieve the network or generate it.
     * Create one if one does not exist.
     *
     * @param string $networkName
     * @return Network
     */
    public function getNetWorkOrGenerate(string $name): Network
    {
        return Network::updateOrCreate(['name' => $name]);
    }
}
