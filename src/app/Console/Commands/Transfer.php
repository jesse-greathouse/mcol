<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Media\TransferManager;

use \Exception;

/**
 * Command for transferring a file.
 */
class Transfer extends Command
{
    // Define the default directories for Windows systems
    const DEFAULT_WINDOWS_VAR_DIR = '%APPDATA%' .  DS . 'var';

    // Define the default directories for Unix-like systems
    const DEFAULT_UNIX_LIKE_VAR_DIR = '$HOME' .  DS . 'var';

    /**
     * Uri of file to be transferred.
     *
     * @var string
     */
    protected $uri;

    /**
     * Path of where the completed transfer will be.
     *
     * @var string
     */
    protected $destination;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:transfer {uri} {destination}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer a file';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $uri = $this->getUri();
        $destination = $this->getDestination();
        $varDir = env('VAR', $this->getDefaultVarDir());
        $tmpDir = "$varDir/transfer";

        // Ensure transfer directory exists
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true); // Create the directory with proper permissions
        }

        $options = ['tmp_dir' => $tmpDir];

        $manager = new TransferManager($uri, $destination, $options);
        $manager->transfer();
    }

    /**
     * Gets and validates the URI as input.
     *
     * @return string
     * @throws Exception If the URI is invalid.
     */
    public function getUri(): string
    {
        // Validate if URI is set and not empty
        if (null === $this->uri) {
            $this->uri = $this->argument('uri');
            if (empty($this->uri)) {
                throw new Exception("Invalid file URI.");
            }
        }

        return $this->uri;
    }

    /**
     * Gets and validates the destination path as input.
     *
     * @return string
     * @throws Exception If the destination is invalid.
     */
    public function getDestination(): string
    {
        // Validate if destination is set and not empty
        if (null === $this->destination) {
            $this->destination = $this->argument('destination');
            if (empty($this->destination)) {
                throw new Exception("Invalid destination path.");
            }
        }

        return $this->destination;
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
