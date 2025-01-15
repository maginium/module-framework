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
 * Command for scaffolding a new Magento 2 service.
 *
 * This command generates the necessary directory structure and starter files
 * for creating a new Magento 2 service within a specified module. It enables
 * users to define the module's namespace, name, and the specific service
 * to be generated. The command ensures that the service follows Magento's
 * conventions and provides an easy way to scaffold a new service class.
 */
#[AsCommand(MakeCommands::SERVICE)]
class ServiceCommand extends GeneratorCommand
{
    /**
     * A brief description of the service.
     */
    protected ?string $description = 'Generate a new service for a Magento 2 module.';

    /**
     * The arguments the service accepts.
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
        'service' => [
            'mode' => self::REQUIRED,
            'description' => 'The service to generate.',
        ],
    ];

    /**
     * Handle the service execution.
     *
     * This method is responsible for executing the service logic: it generates
     * the module's directory structure, creates starter files, and updates the
     * module's `di.xml` configuration.
     *
     * @return int The exit code indicating success or failure of the service execution.
     */
    public function handle(): int
    {
        try {
            // Retrieve the user inputs for the module name, namespace, and service name
            $module = $this->arguments()->getName();
            $service = $this->arguments()->getServiceName();
            $namespace = $this->arguments()->getNamespace();

            // Add a spinner for generating the starter files for the new service
            Prompts::spinner(
                message: 'Generating service file...',
                callback: function() use ($namespace, $module, $service) {
                    $this->generateFile(
                        Path::join($this->codeBaseDir, $namespace, $module, 'Models', $service . '.php'),
                        'service',
                    );
                },
            );

            // Output a success message to the console
            ConsoleOutput::success(Str::format('%1 service generated successfully.', $service));

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
     * populate the stub template when generating the service file.
     *
     * @param  DataObject  $arguments  The arguments passed to the service.
     *
     * @return array The render variables to be used in the stub template.
     */
    protected function getPlaceholders(DataObject $arguments): array
    {
        // Retrieve the namespace and module name from the service arguments
        $namespace = $arguments->getNamespace();
        $module = $arguments->getName();

        // Return an associative array where placeholders are replaced with actual values
        return [
            ':MODULE:' => $module,
            ':NAMESPACE:' => $namespace,
        ];
    }
}
