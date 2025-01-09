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
 * Command for scaffolding a new Magento 2 seeder.
 *
 * This command generates the necessary directory structure and starter files
 * for creating a new Magento 2 seeder within a specified module. It enables
 * users to define the module's namespace, name, and the specific seeder
 * to be generated. The command ensures that the seeder follows Magento's
 * conventions and provides an easy way to scaffold a new seeder class.
 */
#[AsCommand(MakeCommands::SEEDER)]
class SeederCommand extends GeneratorCommand
{
    /**
     * A brief description of the seeder.
     */
    protected ?string $description = 'Generate a new seeder for a Magento 2 module.';

    /**
     * The arguments the seeder accepts.
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
        'seeder' => [
            'mode' => self::REQUIRED,
            'description' => 'The seeder name.',
        ],
    ];

    /**
     * Handle the seeder execution.
     *
     * This method is responsible for executing the seeder logic: it generates
     * the module's directory structure, creates starter files, and updates the
     * module's `di.xml` configuration.
     *
     * @return int The exit code indicating success or failure of the seeder execution.
     */
    protected function handle(): int
    {
        try {
            // Retrieve the user inputs for the module name, namespace, and seeder name
            $module = $this->arguments()->getName();
            $seeder = $this->arguments()->getSeederName();
            $namespace = $this->arguments()->getNamespace();

            // Add a spinner for generating the seeder file
            Prompts::spinner(
                message: Str::format('Generating %1 seeder file...', $seeder),
                callback: function() use ($module, $namespace, $seeder) {
                    // Generate the starter files for the new seeder using the stub generator
                    $this->generateFile(
                        Path::join($this->codeBaseDir, $namespace, $module, 'Setup', 'Patch', 'Data', $seeder) . '.php',
                        'seeder',
                    );
                },
            );

            // Output a success message to the console
            ConsoleOutput::success(Str::format('%1 seeder generated successfully.', $seeder));

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
     * populate the stub template when generating the seeder file.
     *
     * @param  DataObject  $arguments  The arguments passed to the seeder.
     *
     * @return array The render variables to be used in the stub template.
     */
    protected function getPlaceholders(DataObject $arguments): array
    {
        // Retrieve the namespace and module name from the seeder arguments
        $namespace = $arguments->getNamespace();
        $module = $arguments->getName();

        // Return an associative array where placeholders are replaced with actual values
        return [
            ':MODULE:' => $module,
            ':NAMESPACE:' => $namespace,
        ];
    }
}
