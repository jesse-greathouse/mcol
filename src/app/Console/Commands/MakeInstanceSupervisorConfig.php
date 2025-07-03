<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

if (! defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

class MakeInstanceSupervisorConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:make-instance-supervisor-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'With all applicable IRC network channels, create the supervisord config for each.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach ($this->getClients() as $client) {
            $etcDir = env('ETC', '/etc');
            $instanceConfigDir = $etcDir.DS.'supervisor'.DS.'instances';
            $clientConfigFile = $instanceConfigDir.DS.$client->network->name.'.conf';

            if (file_exists($clientConfigFile)) {
                unlink($clientConfigFile);
            }

            $configContent = $this->makeFromTemplate(
                $client->network->name,
                $client->nick->nick
            );

            file_put_contents($clientConfigFile, $configContent);
        }
    }

    /**
     * Retrieves the Network instance based on the provided option.
     *
     * @return Network|null
     */
    protected function getClients(): Collection
    {
        return Client::where('enabled', true)->get();
    }

    protected function makeFromTemplate(string $networkName, string $nick)
    {
        return "[program: $networkName]
process_name=%(ENV_APP_NAME)s_instance_%(program_name)s
environment=PATH=\"%(ENV_OPT)s/php/bin:%(ENV_PATH)s\"
directory=%(ENV_SRC)s
command=php artisan mcol:make-instance --network=$networkName --nick=$nick
autostart=true
autorestart=true
startsecs=30
startretries=0
stopasgroup=true
killasgroup=true
stdout_events_enabled=true
stderr_logfile=%(ENV_LOG_DIR)s/error.log
stdout_logfile=%(ENV_LOG_DIR)s/supervisord.log
stopwaitsecs=3\n";
    }
}
