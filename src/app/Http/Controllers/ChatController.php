<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;

use Inertia\Inertia,
    Inertia\Response;

use App\Models\Client,
    App\Models\Network,
    App\Packet\DownloadQueue,
    App\Settings;

class ChatController
{
    /**
     * Holds a collection of Network names
     *
     * @var Collection
     */
    private $networkList;

    /**
     * Main fiew for the Chat page
     *
     * @return Response
     */
    public function index()
    {
        return Inertia::render('Chat', [
            'queue'     => fn () => DownloadQueue::getQueue(),
            'settings'  => fn (Settings $settings) => $settings->toArray(),
            'networks'  => fn () => $this->getNetworkList(),
            'instances' => fn () => $this->getNetworkClients(),
        ]);
    }

    /**
     * For a list of networks, get an array of clients.
     *
     * @return array <string, Client>
     */
    private function getNetworkClients(): array
    {
        $clients = [];

        foreach($this->getNetworkList() as $network) {
            $client = Client::join('networks', 'networks.id', '=', 'clients.network_id')
                ->where('clients.enabled', true)
                ->where('networks.name', $network)
                ->first();

            $clients[$network] = $client->meta;
        }

        return $clients;
    }

    /**
     * Makes sure the networkList is populated.
     *
     * @return Collection
     */
    private function getNetworkList(): Collection
    {
        if (null === $this->networkList) {
            $this->networkList = Network::all()->pluck('name');
        }

        return $this->networkList;
    }
}
