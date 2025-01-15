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
 * Command for scaffolding a new Magento 2 factory.
 *
 * This command generates the necessary directory structure and starter files
 * for creating a new Magento 2 factory within a specified module. It enables
 * users to define the module's namespace, name, and the specific factory
 * to be generated. The command ensures that the factory follows Magento's
 * conventions and provides an easy way to scaffold a new factory class.
 */
#[AsCommand(MakeCommands::FACTORY)]
class FactoryCommand extends GeneratorCommand
{
    /**
     * A brief description of the factory.
     */
    protected ?string $description = 'Generate a new factory for a Magento 2 module.';

    /**
     * The arguments the factory accepts.
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
        'factory' => [
            'mode' => self::REQUIRED,
            'description' => 'The factory to generate.',
        ],
    ];

    /**
     * Handle the factory execution.
     *
     * This method is responsible for executing the factory logic: it generates
     * the module's directory structure, creates starter files, and updates the
     * module's `di.xml` configuration.
     *
     * @return int The exit code indicating success or failure of the factory execution.
     */
    public function handle(): int
    {
        try {
            // Retrieve the user inputs for the module name, namespace, and factory name
            $module = $this->arguments()->getName();
            $factory = $this->arguments()->getFactoryName();
            $namespace = $this->arguments()->getNamespace();

            // Add a spinner for generating the starter files for the new factory
            Prompts::spinner(
                message: 'Generating factory file...',
                callback: function() use ($namespace, $module, $factory) {
                    $this->generateFile(
                        Path::join($this->codeBaseDir, $namespace, $module, 'Models', $factory . '.php'),
                        'factory',
                    );
                },
            );

            // Output a success message to the console
            ConsoleOutput::success(Str::format('%1 factory generated successfully.', $factory));

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
     * populate the stub template when generating the factory file.
     *
     * @param  DataObject  $arguments  The arguments passed to the factory.
     *
     * @return array The render variables to be used in the stub template.
     */
    protected function getPlaceholders(DataObject $arguments): array
    {
        // Retrieve the namespace and module name from the factory arguments
        $namespace = $arguments->getNamespace();
        $module = $arguments->getName();

        // Return an associative array where placeholders are replaced with actual values
        return [
            ':MODULE:' => $module,
            ':NAMESPACE:' => $namespace,
        ];
    }
}
