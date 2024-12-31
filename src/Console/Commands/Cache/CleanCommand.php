<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Commands\Cache;

use Exception;
use Maginium\Framework\Console\Command;
use Maginium\Framework\Console\Enums\Commands;
use Maginium\Framework\Console\Enums\MagentoCommands;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Prompts;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command for cleaning the Magento 2 cache.
 *
 * This command cleanes the cached data from Magento 2's cache storage backend.
 * Ceaning the cache removes all the cache entries from the storage and forces
 * Magento to regenerate cached data on the next page load or request.
 *
 * It is useful when you want to completely clear all cached data, including
 * content from external cache backends (e.g., Redis or Varnish).
 *
 * The command follows Magento 2's cache cleaning conventions and provides
 * an easy way to clean the cache from the command line.
 */
#[AsCommand(Commands::CACHE_CLEAN)]
class CleanCommand extends Command
{
    /**
     * A brief description of the cache clean command.
     */
    protected ?string $description = 'Clean the cache.';

    /**
     * Handle the cache cleaning execution.
     *
     * This method is responsible for executing the cache cleaning logic. It clears
     * and cleanes all cache entries in the system, ensuring that all cached data
     * is removed from the storage backend. The command provides feedback to indicate
     * whether the cache clean was successful or if an error occurred.
     *
     * @return int The exit code indicating the success or failure of the cache cleaning execution.
     */
    protected function handle(): int
    {
        try {
            // Manage the specific module with a spinner
            Prompts::spinner(
                callback: function() {
                    sleep(1); // Simulating a 1-second delay

                    return $this->callSilently(MagentoCommands::CACHE_CLEAN);
                },
                message: 'Cleaning the cache...',
            );

            ConsoleOutput::success('Cache cleaned and cleaned successfully!');

            // Return the success exit code
            return self::SUCCESS;
        } catch (Exception $e) {
            // If an error occurs, catch the exception and output the error message
            ConsoleOutput::error('Error: ' . $e->getMessage());

            // Return failure exit code
            return self::FAILURE;
        }
    }
}
