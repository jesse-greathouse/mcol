<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Client,
    App\Models\Instance,
    App\Models\Network,
    App\Models\Nick;

use Faker,
    Faker\Generator as FakerGenerator;

class InstanceSeeder extends Seeder
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
            $client = $this->getClientForNickAndNetworkOrGenerate($nick, $network);

            $instance = Instance::where('client_id', $client->id)->first();

            if (null !== $instance) continue;

            Instance::factory()->create([
                'client_id'         => $client->id,
                'desired_status'    => Instance::STATUS_UP,
                'log_uri'           => $this->getLogUriForClient($client),
                'enabled'           => true,
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
     * Returns a client with the given params.
     *
     * @param Nick $nick
     * @param Network $network
     *
     * @return Client
     */
    public function getClientForNickAndNetworkOrGenerate(Nick $nick, Network $network): Client|null
    {
        $client = Client::updateOrCreate(
            ['network_id' => $network->id, 'nick_id' => $nick->id],
            ['enabled' => 1]
        );

        return $client;
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

    public function getLogUriForClient(Client $client): string
    {
        $logDir = env('LOG_DIR', null);
        $instancesDirName = 'instances';
        return sprintf("%s%s%s%s%s%s%s.log",
            $logDir,
            DIRECTORY_SEPARATOR,
            $instancesDirName,
            DIRECTORY_SEPARATOR,
            $client->nick->nick,
            DIRECTORY_SEPARATOR,
            $client->network->name
        );
    }
}
