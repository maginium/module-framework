<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Commands\Make;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Console\Enums\MakeCommands;
use Maginium\Framework\Console\GeneratorCommand;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Prompts;
use Maginium\Framework\Support\Path;
use Maginium\Framework\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command for scaffolding a new Magento 2 migration.
 *
 * This command generates the necessary directory structure and starter files
 * for creating a new Magento 2 migration within a specified module. It enables
 * users to define the module's namespace, name, and the specific migration
 * to be generated. The command ensures that the migration follows Magento's
 * conventions and provides an easy way to scaffold a new migration class.
 */
#[AsCommand(MakeCommands::MIGRATION)]
class MigrationCommand extends GeneratorCommand
{
    /**
     * A brief description of the migration.
     */
    protected ?string $description = 'Generate a new migration for a Magento 2 module.';

    /**
     * The arguments the migration accepts.
     */
    protected array $arguments = [
        'namespace' => [
            'mode' => self::REQUIRED,
            'description' => 'The namespace of the module.',
        ],
        'module' => [
            'mode' => self::REQUIRED,
            'description' => 'The name of the module.',
        ],
        'migration' => [
            'mode' => self::REQUIRED,
            'description' => 'The migration name.',
        ],
    ];

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
        try {
            // Retrieve the user inputs for the module name, namespace, and migration name
            $module = $this->arguments()->getName();
            $namespace = $this->arguments()->getNamespace();
            $migration = $this->arguments()->getMigrationName();

            // Add a spinner for generating the starter files for the new migration
            Prompts::spinner(
                message: 'Generating migration file...',
                callback: function() use ($namespace, $module, $migration) {
                    $this->generateFile(
                        Path::join($this->codeBaseDir, $namespace, $module, 'Setup', 'Patch', 'Schema', $migration) . '.php',
                        'migration',
                    );
                },
            );

            // Output a success message to the console
            ConsoleOutput::success(Str::format('%1 migration generated successfully.', $migration));

            // Return the success exit code
            return self::SUCCESS;
        } catch (Exception $e) {
            // If an error occurs, catch the exception and output the error message
            ConsoleOutput::error('Error: ' . $e->getMessage());

            // Return failure exit code
            return self::FAILURE;
        }
    }

    /**
     * Get the render variables for the file.
     *
     * This method returns an associative array of variables that are used to
     * populate the stub template when generating the migration file.
     *
     * @param  DataObject  $arguments  The arguments passed to the migration.
     *
     * @return array The render variables to be used in the stub template.
     */
    protected function getPlaceholders(DataObject $arguments): array
    {
        // Retrieve the namespace and module name from the migration arguments
        $namespace = $arguments->getNamespace();
        $module = $arguments->getName();

        // Return an associative array where placeholders are replaced with actual values
        return [
            ':MODULE:' => $module,
            ':NAMESPACE:' => $namespace,
        ];
    }
}
