<?php

namespace App\Console\Commands;

use App\Store\PlexMediaServerSettings;
use Deerdama\ConsoleZoo\ConsoleZoo;
use Illuminate\Console\Command;

class ConfigurePlexMediaServerSettings extends Command
{
    use ConsoleZoo;

    // Define the default directories for Windows systems
    const DEFAULT_WINDOWS_VAR_DIR = '%APPDATA%'.DS.'var';

    // Define the default directories for Unix-like systems
    const DEFAULT_UNIX_LIKE_VAR_DIR = '$HOME'.DS.'var';

    /** @var string Command signature */
    protected $signature = 'mcol:plex-media-server-settings';

    /** @var string Command description */
    protected $description = 'Attempts to configure values for Plex Media Server.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $varDir = env('VAR', $this->getDefaultVarDir());
        $store = new PlexMediaServerSettings($varDir);

        foreach ($store->getKeys() as $key) {
            $default = $store->{$key};
            $prompt = 'Set the value of: '.ucfirst($key);

            if (is_array($default)) {
                $default = implode(',', $default);
            }

            $store->{$key} = $this->ask($prompt, $default);
        }

        $this->zoo('The Plex Media Server settings will be saved with the following...', [
            'color' => 'light_blue_bright_2',
            'icons' => ['sparkles'],
            'bold',
            'italic',
        ]);

        $this->newLine();
        $this->displayTable($store->getStorable());

        if ($this->confirm('Do you wish to continue?')) {
            $store->save();
            $this->warn('Plex Media Server settings saved.');
        } else {
            $this->warn('Exiting without saving...');
        }
    }

    /**
     * Display a table of the provided key-value data.
     *
     * @param  array  $data  Associative array of keys and values.
     */
    private function displayTable(array $data): void
    {
        $this->table(['Key', 'Value'], array_map(fn ($key, $value) => ['key' => $key, 'value' => $value], array_keys($data), $data));
    }

    /**
     * Determines the appropriate download directory based on the operating system or environment.
     *
     * @return string The var directory path.
     */
    protected function getDefaultVarDir(): string
    {
        // Default to Windows or Linux/macOS based on the system
        return (PHP_OS_FAMILY === 'Windows')
            ? $this->replaceSystemVariables(self::DEFAULT_WINDOWS_VAR_DIR)
            : $this->replaceSystemVariables(self::DEFAULT_UNIX_LIKE_VAR_DIR);
    }

    /**
     * Replaces system-specific variables with their actual values.
     *
     * @param  string  $path  The path containing system variables.
     * @return string The path with system variables replaced.
     */
    private function replaceSystemVariables(string $path): string
    {
        // Replace %APPDATA% and $HOME with the respective system values
        if (PHP_OS_FAMILY === 'Windows') {
            $path = str_replace('%APPDATA%', getenv('APPDATA'), $path);
        } elseif (PHP_OS_FAMILY === 'Linux' || PHP_OS_FAMILY === 'Darwin') {
            $path = str_replace('$HOME', getenv('HOME'), $path);
        }

        return $path;
    }
}
