<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Network;

class NetworkSeeder extends Seeder
{
    protected Array $networks = [
        'Abjects',
        'Rizon',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->networks as $name) {
            $network = Network::where('name', $name)->first();

            if (null !== $network) continue;

            Network::factory()->create([
                'name' => $name,
            ]);
        }
    }
}
