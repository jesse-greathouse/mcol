<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Network,
    App\Models\Server;

class ServerSeeder extends Seeder
{    
    
    /**
    * @var Array $servers
    */
   protected Array $servers = [
       'Abjects' => 'irc.abjects.net',
   ];

    /**
    * @var Array $networks
    */
    protected Array $networks = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->getServers() as $networkName => $hostName) {
            $network = $this->getNetworkByName($networkName);

            if (!$network) continue;

            Server::updateOrCreate(
                ['host' => $hostName],['network_id' => $network->id]
            );
        }
    }

   /**
    * Get $servers
    *
    * @return  Array
    */ 
   public function getServers(): array
   {
      return $this->servers;
   }

   /**
     * Get an instance of Network by name.
     */ 
    public function getNetworkByName(string $name): Network|null
    {
        return Network::where('name', $name)->first();
    }
}
