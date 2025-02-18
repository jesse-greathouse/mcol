<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;

use Inertia\Inertia,
    Inertia\Response;

use App\Models\Client,
    App\Models\FileDownloadLock,
    App\Models\Network,
    App\Packet\DownloadQueue,
    App\Settings;

class ChatController
{
    /**
     * Holds a collection of Network names.
     *
     * @var Collection
     */
    private Collection $networkList;

    /**
     * Main view for the Chat page.
     *
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('Chat', [
            'queue'     => fn () => DownloadQueue::getQueue(),
            'locks'     => fn () => FileDownloadLock::all()->pluck('file_name')->toArray(),
            'settings'  => fn (Settings $settings) => $settings->toArray(),
            'networks'  => fn () => $this->getNetworkList(),
            'instances' => fn () => $this->getNetworkClients(),
        ]);
    }

    /**
     * For a list of networks, get an array of clients.
     *
     * @return array<string, Client>
     */
    private function getNetworkClients(): array
    {
        $clients = [];

        // Avoid calling getNetworkList repeatedly by storing it in a variable
        $networkList = $this->getNetworkList();

        foreach ($networkList as $network) {
            $client = Client::join('networks', 'networks.id', '=', 'clients.network_id')
                ->where('clients.enabled', true)
                ->where('networks.name', $network)
                ->first();

            if ($client) {
                $clients[$network] = $client->meta;
            }
        }

        return $clients;
    }

    /**
     * Ensures the networkList is populated.
     *
     * @return Collection
     */
    private function getNetworkList(): Collection
    {
        // Populate the list only if it's not already set
        if (is_null($this->networkList)) {
            $this->networkList = Network::all()->pluck('name');
        }

        return $this->networkList;
    }
}
