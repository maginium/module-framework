<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Commands\Make;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Console\Enums\MakeCommands;
use Maginium\Framework\Console\GeneratorCommand;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Filesystem;
use Maginium\Framework\Support\Facades\Prompts;
use Maginium\Framework\Support\Path;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command for scaffolding a new Magento 2 module.
 *
 * This command will generate the necessary directory structure and starter files
 * for creating a new Magento 2 module. It provides an interface for users to define
 * the module's namespace and name.
 */
#[AsCommand(MakeCommands::MODULE)]
class ModuleCommand extends GeneratorCommand
{
    /**
     * A brief description of the command.
     */
    protected ?string $description = 'Scaffold a new Magento 2 module.';

    /**
     * The arguments the command accepts.
     */
    protected array $arguments = [
        'namespace' => [
            'mode' => self::REQUIRED,
            'description' => 'The namespace of the module.',
        ],
        'name' => [
            'mode' => self::REQUIRED,
            'description' => 'The name of the module.',
        ],
    ];

    /**
     * Handle the command execution.
     *
     * This is the core logic for the command that will create the module's directories
     * and generate the starter files using pre-defined stubs.
     *
     * @return int The exit code indicating the success or failure of the command.
     */
    protected function handle(): int
    {
        try {
            // Retrieve arguments from the user input
            $name = $this->arguments()->getName();
            $namespace = $this->arguments()->getNamespace();

            // Add a spinner for creating the necessary directory structure
            Prompts::spinner(
                message: 'Creating directory structure...',
                callback: function() use ($namespace, $name) {
                    $this->createDirectories($namespace, $name);
                },
            );

            // Add a spinner for generating the starter files
            Prompts::spinner(
                message: 'Generating starter files...',
                callback: function() use ($namespace, $name) {
                    $this->createFiles($namespace, $name);
                },
            );

            // Inform the user that the module scaffolding was created successfully
            ConsoleOutput::success('Module scaffolding created successfully.');

            // Return success exit code
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
     * This abstract method must be implemented by child classes to return an associative array
     * of variables to be bound to the stub. It will be used to render the file with the correct data.
     *
     * @param  DataObject  $arguments  The arguments passed to the command.
     *
     * @return array The render variables to be used in the stub.
     */
    protected function getPlaceholders(DataObject $arguments): array
    {
        // Retrieve arguments from the user input
        $name = $arguments->getName();
        $namespace = $arguments->getNamespace();

        return [
            ':MODULE:' => $name,
            ':NAMESPACE:' => $namespace,
        ];
    }

    /**
     * Create the module directory structure.
     *
     * This method creates the necessary directories for a new module, including standard
     * subdirectories like 'Commands', 'Models', 'Controllers', etc. If any directory
     * already exists, it will be skipped to prevent overwriting.
     *
     * @param  string  $namespace  The namespace of the module.
     * @param  string  $name  The name of the module.
     */
    private function createDirectories(string $namespace, string $name): void
    {
        // Define the base path for the module
        $basePath = Path::join($this->codeBaseDir, $namespace, $name);

        // List of standard subdirectories to create
        $directories = [
            'Commands',
            'etc',
            'Models',
            'Listeners',
            'Setup',
            'Setup/Migrations',
            'Setup/Seeds',
            'view',
            'view/adminhtml',
            'view/frontend',
            'Controller',
            'Controller/Adminhtml',
            'Block',
            'Block/Adminhtml',
            'Tests',
            'Tests/Unit',
            'Tests/Features',
        ];

        // Loop through each directory and create it if it doesn't exist
        foreach ($directories as $directory) {
            $path = Path::join($basePath, $directory);

            try {
                // Create the directory if it doesn't already exist
                Filesystem::makeDirectory($path);
            } catch (Exception $e) {
                // Log or handle the error if directory creation fails
                $this->error("Failed to create directory: {$path}. Error: " . $e->getMessage());
            }
        }
    }

    /**
     * Create the module's starter files from stubs.
     *
     * This method generates the essential files for the module, such as `module.xml`,
     * `di.xml`, and other configuration files, by rendering predefined stubs with
     * the module's namespace and name.
     *
     * @param  string  $namespace  The namespace of the module.
     * @param  string  $name  The name of the module.
     */
    private function createFiles(string $namespace, string $name): void
    {
        // List of files to be generated, with their stub sources and target paths
        $files = [
            'module.xml' => [
                'stub' => 'module',
                'target' => Path::join($this->codeBaseDir, $namespace, $name . 'etc', 'module.xml'),
            ],
            'di.xml' => [
                'stub' => 'di',
                'target' => Path::join($this->codeBaseDir, $namespace, $name . 'etc', 'di.xml'),
            ],
            'events.xml' => [
                'stub' => 'events',
                'target' => Path::join($this->codeBaseDir, $namespace, $name . 'etc', 'events.xml'),
            ],
            'registration.php' => [
                'stub' => 'registration',
                'target' => Path::join($this->codeBaseDir, $namespace, $name . 'etc', 'registration.php'),
            ],
            'InstallSchema.php' => [
                'stub' => 'InstallSchema',
                'target' => Path::join($this->codeBaseDir, $namespace, $name . 'Setup', 'InstallSchema.php'),
            ],
            'TestCase.php' => [
                'stub' => 'TestCase.php',
                'target' => Path::join($this->codeBaseDir, $namespace, $name . 'Tests', 'TestCase.php'),
            ],
        ];

        // Loop through each file definition and create the file
        foreach ($files as $file => $info) {
            try {
                // Attempt to generate the file
                $this->generateFile($info['target'], $info['stub']);
            } catch (Exception $e) {
                // Re-throw the exception to ensure the error is handled upstream
                throw $e;
            }
        }
    }
}
