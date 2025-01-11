<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Console;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Console\GeneratorCommand;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Prompts;
use Maginium\Framework\Support\Path;
use Maginium\Framework\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command for scaffolding a new Magento 2 filter.
 *
 * This command generates the necessary directory structure and starter files
 * for creating a new Magento 2 filter within a specified module. It enables
 * users to define the module's namespace, name, and the specific filter
 * to be generated. The command ensures that the filter follows Magento's
 * conventions and provides an easy way to scaffold a new filter class.
 */
#[AsCommand('make:filter')]
class FilterMakeCommand extends GeneratorCommand
{
    /**
     * A brief description of the filter.
     */
    protected ?string $description = 'Create a new filter class';

    /**
     * The arguments the filter accepts.
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
        'filter' => [
            'mode' => self::REQUIRED,
            'description' => 'The filter to generate.',
        ],
    ];

    /**
     * Handle the filter execution.
     *
     * This method is responsible for executing the filter logic: it generates
     * the module's directory structure, creates starter files, and updates the
     * module's `di.xml` configuration.
     *
     * @return int The exit code indicating success or failure of the filter execution.
     */
    protected function handle(): int
    {
        try {
            // Retrieve the user inputs for the module name, namespace, and filter name
            $module = $this->arguments()->getName();
            $filter = $this->arguments()->getFillterName();
            $namespace = $this->arguments()->getNamespace();

            // Add a spinner for generating the starter files for the new filter
            Prompts::spinner(
                message: 'Generating filter file...',
                callback: function() use ($namespace, $module, $filter) {
                    $this->generateFile(
                        Path::join($this->codeBaseDir, $namespace, $module, 'Models', $filter . '.php'),
                        'filter',
                    );
                },
            );

            // Output a success message to the console
            ConsoleOutput::success(Str::format('%1 filter generated successfully.', $filter));

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
     * populate the stub template when generating the filter file.
     *
     * @param  DataObject  $arguments  The arguments passed to the filter.
     *
     * @return array The render variables to be used in the stub template.
     */
    protected function getPlaceholders(DataObject $arguments): array
    {
        // Retrieve the namespace and module name from the filter arguments
        $module = $arguments->getName();
        $filter = $arguments->getFillterName();
        $namespace = $arguments->getNamespace();

        // Return an associative array where placeholders are replaced with actual values
        return [
            ':MODULE:' => $module,
            ':FILTER:' => $filter,
            ':NAMESPACE:' => $namespace,
        ];
    }
}
