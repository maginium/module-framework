<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Commands\Make;

use Exception;
use Maginium\Framework\Console\Enums\MakeCommands;
use Maginium\Framework\Console\GeneratorCommand;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Prompts;
use Maginium\Framework\Support\Path;
use Maginium\Framework\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command for scaffolding a new Magento 2 middleware.
 *
 * This command generates the necessary directory structure and starter files
 * for creating a new Magento 2 middleware within a specified module. It enables
 * users to define the module's namespace, name, and the specific middleware
 * to be generated. The command ensures that the middleware follows Magento's
 * conventions and provides an easy way to scaffold a new middleware class.
 */
#[AsCommand(MakeCommands::MIDDLEWARE)]
class MiddlewareCommand extends GeneratorCommand
{
    /**
     * A brief description of the middleware.
     */
    protected ?string $description = 'Generate a new middleware for a Magento 2 module.';

    /**
     * The arguments the middleware accepts.
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
        'middleware' => [
            'mode' => self::REQUIRED,
            'description' => 'The middleware to generate.',
        ],
    ];

    /**
     * Handle the middleware execution.
     *
     * This method is responsible for executing the middleware logic: it generates
     * the module's directory structure, creates starter files, and updates the
     * module's `di.xml` configuration.
     *
     * @return int The exit code indicating success or failure of the middleware execution.
     */
    protected function handle(): int
    {
        try {
            // Retrieve the user inputs for the module name, namespace, and middleware name
            $module = $this->arguments()->getName();
            $middleware = $this->arguments()->getMiddlewareName();
            $namespace = $this->arguments()->getNamespace();

            // Add a spinner for generating the starter files for the new middleware
            Prompts::spinner(
                message: 'Generating middleware file...',
                callback: function() use ($namespace, $module, $middleware) {
                    $this->generateFile(
                        Path::join($this->codeBaseDir, $namespace, $module, 'Models', $middleware . '.php'),
                        'middleware',
                    );
                },
            );

            // Output a success message to the console
            ConsoleOutput::success(Str::format('%1 middleware generated successfully.', $middleware));

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
     * populate the stub template when generating the middleware file.
     *
     * @param  DataObject  $arguments  The arguments passed to the middleware.
     *
     * @return array The render variables to be used in the stub template.
     */
    protected function getPlaceholders(DataObject $arguments): array
    {
        // Retrieve the namespace and module name from the middleware arguments
        $namespace = $arguments->getNamespace();
        $module = $arguments->getName();

        // Return an associative array where placeholders are replaced with actual values
        return [
            ':MODULE:' => $module,
            ':NAMESPACE:' => $namespace,
        ];
    }
}
