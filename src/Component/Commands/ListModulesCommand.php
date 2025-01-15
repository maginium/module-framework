<?php

declare(strict_types=1);

namespace Maginium\Framework\Component\Commands;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Console\Enums\Commands;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Prompts;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command for listing all modules.
 *
 * This command lists all modules along with their enabled status and paths.
 * It extends the `ModuleCommand` abstract class, which provides the common functionality
 * for retrieving and working with modules.
 */
#[AsCommand(Commands::MODULE_LIST)]
class ListModulesCommand extends ModuleCommand
{
    /**
     * A brief description of the list modules command.
     *
     * This property provides a short description of what the command does.
     * It will be shown when running `php bin/magento list` to list available commands.
     */
    protected ?string $description = 'List all modules with their enabled status and paths.';

    /**
     * The arguments the command accepts.
     */
    protected array $arguments = [
        'module' => [
            'mode' => self::OPTIONAL,
            'description' => 'The name of the module.',
        ],
    ];

    /**
     * Return the type of action for enabling the module.
     *
     * @return string The action type ('enable').
     */
    public function getType(): string
    {
        return 'list';
    }

    /**
     * Handle the execution of the list modules command.
     *
     * This method is responsible for executing the logic of the `module:list` command.
     * It calls the `getModules` method (inherited from the `ModuleCommand` class) to retrieve
     * the list of modules, and then outputs the module details in a table format.
     *
     * If there are no modules or an error occurs, an appropriate error message is shown.
     *
     * @return int The exit code indicating the success or failure of the command execution.
     */
    public function handle(): int
    {
        try {
            // Fetch modules based on the 'module' argument, if provided
            $moduleName = $this->arguments()->getModule();
            $modules = $this->fetchModules($moduleName);

            // Check if no modules were found or if there was an error fetching the modules
            if ($modules->isEmpty()) {
                // Output an error message and return failure code if no modules found
                ConsoleOutput::error('No modules found or unable to read config.php.');

                return self::FAILURE;
            }

            // Map the modules to include the "Enabled" status with color
            $modulesFormatted = $modules->map(function($module) {
                // Return the formatted row with the module's name, status, and path
                return [
                    'Name' => $module->getName(),
                    'Path' => $module->getPath(),
                    'Enabled' => $module->getColoredStatus(),
                    'Priority' => $module->getPriority(),
                ];
            });

            // Output the formatted modules in a table format
            Prompts::table(
                ['Name', 'Path', 'Enabled', 'Priority'],  // Table headers
                $modulesFormatted->all(),  // The list of modules to be displayed
            );

            // Return the success exit code if the operation was successful
            return self::SUCCESS;
        } catch (Exception $e) {
            // Catch any exception that occurs and output the error message
            ConsoleOutput::error('Error: ' . $e->getMessage());

            // Return failure exit code if an exception occurs
            return self::FAILURE;
        }
    }
}
