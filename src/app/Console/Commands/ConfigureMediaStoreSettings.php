<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Store\MediaStoreSettings;

class ConfigureMediaStoreSettings extends Command
{
    use \Deerdama\ConsoleZoo\ConsoleZoo;

    /**
     * @var string
     */
    protected $signature = 'mcol:media-store-settings';

    /**
     * @var string
     */
    protected $description = 'Attempts to configure values for the media store.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $varDir = env('VAR', '/var');
        $store = new MediaStoreSettings($varDir);

        foreach ($store->getKeys() as $key) {
            $default = $store->{$key};
            $prompt = "Set the value of: " . ucfirst($key);
            if (is_array($default)) {
                $default = implode(',', $default);
            }

            $input = $this->ask($prompt, $default);
            $store->{$key} = explode(',', $input);
        }

        $this->zoo("The Media Store settings will be saved with the following...", [
            'color' => 'light_blue_bright_2',
            'icons' => ['sparkles'],
            'bold',
            'italic',
        ]);
        $this->newLine();

        $this->showTable($store->getStorable());

        if ($this->confirm('Do you wish to continue?')) {
            $store->save();
            $this->warn("Media Store settings saved.");
        } else {
            $this->warn("Exiting without saving...");
        }
    }

    private function showTable($data) {
        $headers = ['Key', 'Value'];
        $body = [];

        foreach($data as $key => $value) {
            foreach($value as $line) {
                $body[] = ['key' => $key, 'value' => $line];
                $key = '';
            } 
        }

        $this->table($headers, $body);
    }
}
