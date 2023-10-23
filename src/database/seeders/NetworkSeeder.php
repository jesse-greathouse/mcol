<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Network;

class NetworkSeeder extends Seeder
{
    protected Array $networks = [
        'Abjects',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->getNetworks() as $name) {
            $network = Network::where('name', $name)->first();

            if (NULL !== $network) continue;

            Network::factory()->create([
                'name' => $name,
            ]);
        }
    }

    /**
     * Get the value of networks
     */ 
    public function getNetworks()
    {
        return $this->networks;
    }
}
