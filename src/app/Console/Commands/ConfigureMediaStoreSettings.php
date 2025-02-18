<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Deerdama\ConsoleZoo\ConsoleZoo;

use App\Store\MediaStoreSettings;

// Define DS constant for cross-platform compatibility if not already defined
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

class ConfigureMediaStoreSettings extends Command
{
    use ConsoleZoo;

    // Define the default directories for Windows systems
    const DEFAULT_WINDOWS_VAR_DIR = '%APPDATA%' .  DS . 'var';

    // Define the default directories for Unix-like systems
    const DEFAULT_UNIX_LIKE_VAR_DIR = '$HOME' .  DS . 'var';

    /** @var string The command signature. */
    protected string $signature = 'mcol:media-store-settings';

    /** @var string The command description. */
    protected string $description = 'Attempts to configure values for the media store.';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle(): void
    {
        $varDir = env('VAR', $this->getDefaultVarDir());
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
        $this->displayTable($store->getStorable());

        if ($this->confirm('Do you wish to continue?')) {
            $store->save();
            $this->warn("Media Store settings saved.");
        } else {
            $this->warn("Exiting without saving...");
        }
    }

    /**
     * Display data in a table format.
     *
     * @param array $data Key-value pairs to display.
     * @return void
     */
    private function displayTable(array $data): void
    {
        $headers = ['Key', 'Value'];
        $body = [];

        foreach ($data as $key => $values) {
            foreach ($values as $index => $line) {
                $body[] = ['key' => $index === 0 ? $key : '', 'value' => $line];
            }
        }

        $this->table($headers, $body);
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
     * @param string $path The path containing system variables.
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
