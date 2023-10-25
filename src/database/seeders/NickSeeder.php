<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Nick,
    App\Models\Network;

class NickSeeder extends Seeder
{

    /**
     * List of names for nicks
     *
     * @var Array
     */
    protected Array $nicks = [
        'Abjects' => 'MediaEnjoyer_201'
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->getNicks() as $networkName => $name) {
            $nick = Nick::where('nick', $name)->first();
            $network = Network::where('name', $networkName)->first();

            if (NULL !== $nick ) continue;

            Nick::factory()->create([
                'nick' => $name,
                'network_id' => $network->id,
            ]);
        }
    }

    /**
     * Get list of names for nicks
     *
     * @return  Array
     */ 
    public function getNicks(): array
    {
        return $this->nicks;
    }
}
