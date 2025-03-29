<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Client,
    App\Models\Network,
    App\Models\Nick;

use Faker,
    Faker\Generator as FakerGenerator;

class ClientSeeder extends Seeder
{
    /**
     * Instance of Faker
     *
     * @property FakerGenerator $faker
     */
    protected $faker;

    // List of networks to be populated.
    protected array $networks = ['Abjects', 'Rizon'];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->networks as $name) {
            $network = $this->getNetworkOrGenerate($name);
            $nick = $this->getNickForNetworkOrGenerate($network);

            $client = Client::where('network_id', $network->id)
                ->where('nick_id', $nick->id)
                ->first();

            if (null !== $client) continue;

            Client::factory()->create([
                'nick_id'       => $nick->id,
                'network_id'    => $network->id,
                'enabled'       => true,
                'meta'          => [],
            ]);
        }
    }

    /**
     * With a Network instance, get the associated Nick.
     * Create one if one does not exist.
     *
     * @param Network $network
     * @return Nick
     */
    public function getNickForNetworkOrGenerate(Network $network): Nick
    {
        $nick = Nick::where('network_id', $network->id)->first();

        if (null !== $nick ) return $nick;

        // Creates a random nickname by combining two random words.
        // Glued together with a _ (underscore).
        $words = $this->getFaker()->words(2);
        $name = implode('_', $words);

        $nick = Nick::factory()->create([
            'nick' => $name,
            'network_id' => $network->id,
        ]);

        return $nick;
    }

    /**
     * With a Network name string, retrieve the network or generate it.
     * Create one if one does not exist.
     *
     * @param string $networkName
     * @return Network
     */
    public function getNetWorkOrGenerate(string $name): Network
    {
        return Network::updateOrCreate(['name' => $name]);
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
