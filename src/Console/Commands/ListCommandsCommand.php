<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Commands;

use Maginium\Framework\Console\Command;
use Maginium\Framework\Console\Enums\Commands;
use Maginium\Framework\Support\Facades\Prompts;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command to list all available commands in the system.
 *
 * This command lists all the commands available in the system, including the built-in
 * Magento commands and any custom commands added by the developer.
 */
#[AsCommand(Commands::LIST_COMMANDS)]
class ListCommandsCommand extends Command
{
    /**
     * A brief description of the list commands functionality.
     */
    protected ?string $description = 'List all available commands in the system.';

    /**
     * Handle the migration execution.
     *
     * This method is responsible for executing the migration logic: it generates
     * the module's directory structure, creates starter files, and updates the
     * module's `di.xml` configuration.
     *
     * @return int The exit code indicating success or failure of the migration execution.
     */
    public function handle(): int
    {
        // Get all commands from the console application
        $commands = $this->getApplication()->all();

        // Show the spinner while fetching commands
        Prompts::spinner(
            message: 'Fetching commands...',
            callback: function() {
                sleep(1); // Simulating a 1-second delay to show the spinner
            },
        );

        // Prepare the data for the table
        $rows = [];

        foreach ($commands as $command) {
            $rows[] = [$command->getName(), $command->getDescription()];
        }

        // Display the table with the command names and descriptions
        Prompts::table(
            ['Command', 'Description'],  // Table headers
            $rows, // The list of commands to be displayed
        );

        // Return success exit code (0 indicates success)
        return self::SUCCESS;
    }
}
