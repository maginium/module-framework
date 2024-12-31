<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Commands;

use Exception;
use Maginium\Framework\Console\Command;
use Maginium\Framework\Console\Enums\Commands;
use Maginium\Framework\Console\Enums\MagentoCommands;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Prompts;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command for generating a new database seeder.
 *
 * This command scaffolds the necessary directory structure and starter files
 * for creating a new database seeder within a specified module. It ensures
 * adherence to conventions and provides an easy way to scaffold the seeder class.
 *
 * The generated seeder is used to populate the database with sample or default
 * data for module functionality during development or testing.
 */
#[AsCommand(Commands::DB_SEED)]
class SeedCommand extends Command
{
    /**
     * A brief description of the seeder command.
     */
    protected ?string $description = 'Generate a new seeder for a module.';

    /**
     * Handle the seeder execution.
     *
     * This method executes the seeder logic, runs the 'setup:upgrade' command,
     * and handles any errors during execution while providing user feedback.
     *
     * @return int The exit code indicating the success or failure of the seeder execution.
     */
    protected function handle(): int
    {
        try {
            // Execute the setup:upgrade command with a spinner
            Prompts::spinner(
                message: 'Running seeding...',
                callback: function() {
                    $this->call(MagentoCommands::SETUP_UPGRADE);
                },
            );

            // Output success message
            ConsoleOutput::success('Seeder setup completed successfully!');

            // Return the success exit code
            return self::SUCCESS;
        } catch (Exception $e) {
            // If an error occurs, catch the exception and output the error message
            ConsoleOutput::error('Error during seeder setup: ' . $e->getMessage());

            // Return failure exit code
            return self::FAILURE;
        }
    }
}
