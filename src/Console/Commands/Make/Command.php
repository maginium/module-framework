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
use Maginium\Framework\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Command for scaffolding a new Magento 2 command.
 *
 * This command generates the necessary directory structure and starter files
 * for creating a new Magento 2 command within a specified module. It enables
 * users to define the module's namespace, name, and the specific command
 * to be generated. The command ensures that the module follows Magento's
 * convention for structuring commands and provides an easy way to scaffold
 * a new command class.
 */
#[AsCommand(MakeCommands::COMMAND)]
class Command extends GeneratorCommand
{
    /**
     * A brief description of the command.
     */
    protected ?string $description = 'Generate a new command for a Magento 2 module.';

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
        'command-name' => [
            'mode' => self::REQUIRED,
            'description' => 'The name of the command to be generated.',
        ],
    ];

    /**
     * Handle the command execution.
     *
     * This method is responsible for executing the command logic: it generates
     * the module's directory structure, creates starter files, and updates the
     * module's `di.xml` configuration.
     *
     * @return int The exit code indicating success or failure of the command execution.
     */
    public function handle(): int
    {
        try {
            // Retrieve the user inputs for the module name, namespace, and command name
            $module = $this->arguments()->getName();
            $namespace = $this->arguments()->getNamespace();
            $command = $this->arguments()->getCommandName();

            // Add a spinner for generating the starter files for the new command
            Prompts::spinner(
                message: 'Generating command file...',
                callback: function() use ($namespace, $module, $command) {
                    $this->generateFile(
                        Path::join($this->codeBaseDir, $namespace, $module, 'Commands', $command . '.php'),
                        'command',
                    );
                },
            );

            // Add a spinner for updating the module's `di.xml` configuration
            Prompts::spinner(
                message: 'Updating di.xml with the new command...',
                callback: function() use ($namespace, $module, $command) {
                    $this->addCommandToDi($namespace, $module, $command);
                },
            );

            // Output a success message to the console
            ConsoleOutput::success(Str::format('%1 command generated successfully.', $command));

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
     * populate the stub template when generating the command file.
     *
     * @param  DataObject  $arguments  The arguments passed to the command.
     *
     * @return array The render variables to be used in the stub template.
     */
    protected function getPlaceholders(DataObject $arguments): array
    {
        // Retrieve the namespace and module name from the command arguments
        $module = $arguments->getName();
        $namespace = $arguments->getNamespace();

        // Return an associative array where placeholders are replaced with actual values
        return [
            ':MODULE:' => $module,
            ':NAMESPACE:' => $namespace,
        ];
    }

    /**
     * Add the command to the module's `di.xml` configuration file.
     *
     * This method updates the `di.xml` file for the module to register the new command
     * by adding it to the dependency injection configuration under the appropriate
     * type and arguments section.
     *
     * @param  string  $namespace  The namespace of the module.
     * @param  string  $module  The name of the module.
     * @param  string  $command  The name of the generated command.
     */
    protected function addCommandToDi(string $namespace, string $module, string $command): void
    {
        // Load the `di.xml` file for the module
        $diFilePath = Path::join($this->codeBaseDir, $namespace, $module, 'etc', 'di.xml');

        // Check if the file exists before loading
        if (! Filesystem::exists($diFilePath)) {
            $this->error("The di.xml file does not exist: {$diFilePath}");

            return;
        }

        $xml = simplexml_load_file($diFilePath);

        // Ensure the root <config> node is present
        if (! $xml->getName() === 'config') {
            $this->error('Invalid XML structure in di.xml: Root <config> node missing.');

            return;
        }

        // Check if <type name="Magento\Framework\Console\CommandList"> exists
        $commandListNode = $xml->xpath('//type[@name="Magento\Framework\Console\CommandList"]');

        // If <type name="Magento\Framework\Console\CommandList"> does not exist, create it
        if (empty($commandListNode)) {
            // Add the <type> node for Magento\Framework\Console\CommandList if not present
            $typeNode = $xml->addChild('type');
            $typeNode->addAttribute('name', 'Magento\Framework\Console\CommandList');
        } else {
            // If it exists, use the existing node
            $typeNode = $commandListNode[0];
        }

        // Ensure <arguments> node exists under the <type> node
        if (! isset($typeNode->arguments)) {
            $typeNode->addChild('arguments');
        }

        // Ensure <argument name="commands" type="object"> exists under <arguments>
        $argumentsNode = $typeNode->arguments;
        $argumentNode = $argumentsNode->xpath('argument[@name="commands"]');

        if (empty($argumentNode)) {
            // If <argument name="commands"> does not exist, create it
            $argumentNode = $argumentsNode->addChild('argument');
            $argumentNode->addAttribute('name', 'commands');
            $argumentNode->addAttribute('type', 'object');
        } else {
            // If the argument exists, use it
            $argumentNode = $argumentNode[0];
        }

        // Add the command item with the required format inside <argument>
        $commandItem = $argumentNode->addChild('item', $namespace . '\\' . $module . '\\Commands\\' . $command);
        $commandItem->addAttribute(
            'name',
            Str::replace(SP, '_', Str::lower(Path::join($namespace, $module, 'Commands', $command))),
        );
        $commandItem->addAttribute('xsi:type', 'object');

        // Format the XML with proper indentation using DOMDocument
        $dom = $this->createDOMDocument(['version' => '1.0', 'encoding' => 'UTF-8']);
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        // Load the SimpleXML object into DOMDocument for pretty formatting
        $dom->loadXML($xml->asXML());

        // Replace the standard indentation with 4 tabs
        $formattedXml = Str::replace('  ', "\t", $dom->saveXML());

        // Save the formatted XML to the file
        Filesystem::put($diFilePath, $formattedXml);

        // Output a success message
        $this->info("Command successfully added to di.xml for {$namespace}/{$module}.");
    }
}
