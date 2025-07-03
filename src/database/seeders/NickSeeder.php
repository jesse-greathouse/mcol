<?php

namespace Database\Seeders;

use App\Models\Network;
use App\Models\Nick;
use Faker;
use Faker\Generator as FakerGenerator;
use Illuminate\Database\Seeder;

class NickSeeder extends Seeder
{
    /**
     * Instance of Faker
     *
     * @property FakerGenerator $faker
     */
    protected $faker;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $networks = Network::all();

        foreach ($networks as $network) {
            $nick = Nick::where('network_id', $network->id)->first();

            if ($nick !== null) {
                continue;
            }

            // Creates a random nickname by combining two random words.
            // Glued together with a _ (underscore).
            $words = $this->getFaker()->words(2);
            $name = implode('_', $words);

            Nick::factory()->create([
                'nick' => $name,
                'network_id' => $network->id,
            ]);
        }
    }

    /**
     * Provides the class instance of faker.
     * Creates a new faker instance if it has not been created yet.
     */
    public function getFaker(): FakerGenerator
    {
        if ($this->faker === null) {
            $this->faker = Faker\Factory::create();
        }

        return $this->faker;
    }
}
