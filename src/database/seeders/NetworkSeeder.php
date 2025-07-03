<?php

namespace Database\Seeders;

use App\Models\Network;
use Illuminate\Database\Seeder;

class NetworkSeeder extends Seeder
{
    protected array $networks = [
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

            if ($network !== null) {
                continue;
            }

            Network::factory()->create([
                'name' => $name,
            ]);
        }
    }
}
