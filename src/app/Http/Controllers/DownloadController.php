<?php

namespace App\Http\Controllers;

use App\Media\HeroBanner;
use App\Models\Client;
use App\Models\FileDownloadLock;
use App\Models\Network;
use App\Packet\DownloadQueue;
use App\Settings;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DownloadController
{
    /**
     * Holds a collection of Network names.
     */
    private ?Collection $networkList = null;

    /**
     * Main view for the Chat page.
     */
    public function index(): Response
    {
        return Inertia::render('Download', [
            'settings' => fn (Settings $settings) => $settings->toArray(), // Get settings as array.
            'queue' => fn () => DownloadQueue::getQueue(),
            'locks' => fn () => FileDownloadLock::all()->pluck('file_name')->toArray(),
            'networks' => fn () => $this->getNetworkList(),
            'instances' => fn () => $this->getNetworkClients(),
            'hero' => fn () => (new HeroBanner)->toSvg(),
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
