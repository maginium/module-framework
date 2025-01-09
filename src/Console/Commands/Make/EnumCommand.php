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
 * Command for scaffolding a new Magento 2 enum.
 *
 * This command generates the necessary directory structure and starter files
 * for creating a new Magento 2 enum within a specified module. It enables
 * users to define the module's namespace, name, and the specific enum
 * to be generated. The command ensures that the enum follows Magento's
 * conventions and provides an easy way to scaffold a new enum class.
 */
#[AsCommand(MakeCommands::ENUM)]
class EnumCommand extends GeneratorCommand
{
    /**
     * A brief description of the enum.
     */
    protected ?string $description = 'Generate a new enum for a Magento 2 module.';

    /**
     * The arguments the enum accepts.
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
        'enum' => [
            'mode' => self::REQUIRED,
            'description' => 'The enum to generate.',
        ],
    ];

    /**
     * Handle the enum execution.
     *
     * This method is responsible for executing the enum logic: it generates
     * the module's directory structure, creates starter files, and updates the
     * module's `di.xml` configuration.
     *
     * @return int The exit code indicating success or failure of the enum execution.
     */
    protected function handle(): int
    {
        try {
            // Retrieve the user inputs for the module name, namespace, and enum name
            $module = $this->arguments()->getName();
            $enum = $this->arguments()->getEnumName();
            $namespace = $this->arguments()->getNamespace();

            // Add a spinner for generating the starter files for the new enum
            Prompts::spinner(
                message: 'Generating enum file...',
                callback: function() use ($namespace, $module, $enum) {
                    $this->generateFile(
                        Path::join($this->codeBaseDir, $namespace, $module, 'Models', $enum . '.php'),
                        'enum',
                    );
                },
            );

            // Output a success message to the console
            ConsoleOutput::success(Str::format('%1 enum generated successfully.', $enum));

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
     * populate the stub template when generating the enum file.
     *
     * @param  DataObject  $arguments  The arguments passed to the enum.
     *
     * @return array The render variables to be used in the stub template.
     */
    protected function getPlaceholders(DataObject $arguments): array
    {
        // Retrieve the namespace and module name from the enum arguments
        $namespace = $arguments->getNamespace();
        $module = $arguments->getName();

        // Return an associative array where placeholders are replaced with actual values
        return [
            ':MODULE:' => $module,
            ':NAMESPACE:' => $namespace,
        ];
    }
}
