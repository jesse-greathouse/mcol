<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Nick,
    App\Models\Network;

use Faker,
    Faker\Generator as FakerGenerator;

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

            if (null !== $nick ) continue;

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
     *
     * @return FakerGenerator
     */
    public function getFaker(): FakerGenerator
    {
        if (null === $this->faker) {
            $this->faker = Faker\Factory::create();
        }

        return $this->faker;
    }
}
