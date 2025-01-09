<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Commands\Cache;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Console\Command;
use Maginium\Framework\Console\Enums\Commands;
use Maginium\Framework\Console\Enums\MagentoCommands;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Filesystem;
use Maginium\Framework\Support\Facades\Prompts;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command for flushing the cache.
 *
 * This command clears cached data to ensure outdated or stale cache entries are removed.
 * It is useful for resetting the system cache state after changes to configuration files,
 * layouts, or other cached data.
 */
#[AsCommand(Commands::CACHE_FLUSH)]
class FlushCommand extends Command
{
    /**
     * A brief description of the cache flush command.
     */
    protected ?string $description = 'Flush the cache.';

    /**
     * Handle the cache flushing execution.
     *
     * This method flushes all caches in the system to ensure that the next page load
     * or request uses the most up-to-date data. Feedback is provided to indicate success
     * or failure.
     *
     * @return int The exit code indicating the success or failure of the cache flushing execution.
     */
    protected function handle(): int
    {
        try {
            // Display a spinner before starting the cache flush process
            Prompts::spinner(
                message: 'Preparing to flush the cache...',
                callback: function() {
                    sleep(1);
                },
            );

            // Initialize the progress bar for cache flushing with 5 steps
            $progress = Prompts::progress('Flushing cache...', 5);

            $progress->start();

            // Step 1: Delete the general cache directory 'var/cache'
            $progress->advance();
            Prompts::spinner(
                message: 'Deleting var/cache...',
                callback: function() {
                    sleep(1);
                    Filesystem::delete('var/cache');
                },
            );

            // Step 2: Delete the page cache directory 'var/page_cache'
            $progress->advance();
            Prompts::spinner(
                message: 'Deleting var/page_cache...',
                callback: function() {
                    sleep(1);
                    Filesystem::delete('var/page_cache');
                },
            );

            // Step 3: Delete the generated code directory 'var/generation'
            $progress->advance();
            Prompts::spinner(
                message: 'Deleting var/generation...',
                callback: function() {
                    sleep(1);
                    Filesystem::delete('var/generation');
                },
            );

            // Step 4: Delete the dependency injection (DI) cache directory 'var/di'
            $progress->advance();
            Prompts::spinner(
                message: 'Deleting var/di...',
                callback: function() {
                    sleep(1);
                    Filesystem::delete('var/di');
                },
            );

            // Step 5: Execute the cache flush command
            $progress->advance();
            Prompts::spinner(
                message: 'Executing cache flush...',
                callback: function() {
                    sleep(1);
                    $this->callSilently(MagentoCommands::CACHE_FLUSH);
                },
            );

            // Complete the progress bar
            $progress->finish();

            // Output success message after the operation is complete
            ConsoleOutput::success('Cache flushed successfully!');

            // Return the success exit code
            return self::SUCCESS;
        } catch (Exception $e) {
            // Output an error message if the cache flush fails
            ConsoleOutput::error('Error flushing cache: ' . $e->getMessage());

            // Return failure exit code
            return self::FAILURE;
        }
    }
}
