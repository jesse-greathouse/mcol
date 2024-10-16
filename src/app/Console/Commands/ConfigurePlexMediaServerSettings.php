<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Store\PlexMediaServerSettings;

class ConfigurePlexMediaServerSettings extends Command
{
    use \Deerdama\ConsoleZoo\ConsoleZoo;

    /**
     * @var string
     */
    protected $signature = 'mcol:plex-media-server-settings';

    /**
     * @var string
     */
    protected $description = 'Attempts to configure values for Plex Media Server.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $varDir = env('VAR', '/var');
        $store = new PlexMediaServerSettings($varDir);

        foreach ($store->getKeys() as $key) {
            $default = $store->{$key};
            $prompt = "Set the value of: " . ucfirst($key);
            if (is_array($default)) {
                $default = implode(',', $default);
            }

            $input = $this->ask($prompt, $default);
            $store->{$key} = $input;
        }

        $this->zoo("The Plex Media Server settings will be saved with the following...", [
            'color' => 'light_blue_bright_2',
            'icons' => ['sparkles'],
            'bold',
            'italic',
        ]);
        $this->newLine();

        $this->showTable($store->getStorable());

        if ($this->confirm('Do you wish to continue?')) {
            $store->save();
            $this->warn("Plex Media Server settings saved.");
        } else {
            $this->warn("Exiting without saving...");
        }
    }

    private function showTable($data) {
        $headers = ['Key', 'Value'];
        $body = [];

        foreach($data as $key => $value) {
            $body[] = ['key' => $key, 'value' => $value];
        }

        $this->table($headers, $body);
    }
}
