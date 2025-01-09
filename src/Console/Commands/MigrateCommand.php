<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Commands;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Console\Command;
use Maginium\Framework\Console\Enums\Commands;
use Maginium\Framework\Console\Enums\MagentoCommands;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Prompts;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command for performing database migrations.
 *
 * This command facilitates the migration of database schema changes and data
 * updates for a specified module. It ensures the database schema stays in sync
 * with the module's requirements and provides feedback during the process.
 */
#[AsCommand(Commands::DB_MIGRATE)]
class MigrateCommand extends Command
{
    /**
     * A brief description of the migration command.
     */
    protected ?string $description = 'Perform database migrations for a module.';

    /**
     * Handle the migration execution.
     *
     * This method executes the migration logic by running necessary setup commands
     * and processing migration scripts to update the database schema and data.
     * It provides feedback about the success or failure of the process.
     *
     * @return int The exit code indicating the success or failure of the migration execution.
     */
    protected function handle(): int
    {
        try {
            // Execute the setup:upgrade command with a spinner
            Prompts::spinner(
                message: 'Running migration...',
                callback: function() {
                    $this->call(MagentoCommands::SETUP_UPGRADE);
                },
            );

            // Output success message
            ConsoleOutput::success('Database migrations completed successfully!');

            // Return the success exit code
            return self::SUCCESS;
        } catch (Exception $e) {
            // If an error occurs, catch the exception and output the error message
            ConsoleOutput::error('Error during migration: ' . $e->getMessage());

            // Return failure exit code
            return self::FAILURE;
        }
    }
}
