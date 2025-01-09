<?php

declare(strict_types=1);

namespace Maginium\Framework\Console;

use Illuminate\Console\OutputStyleFactory;
use Magento\Framework\App\State;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Support\DataObject;
use ReflectionFunctionFactory;
use Symfony\Component\Console\Input\InputOptionFactory;

/**
 * Class GeneratorCommand.
 *
 * This abstract class serves as a base for console commands, providing common functionality
 * and ensuring that child commands implement the `getRenderVariables` method and other necessary logic.
 */
abstract class GeneratorCommand extends Command
{
    /**
     * The base directory for the Magento code.
     */
    protected string $codeBaseDir = 'app/code/';

    /**
     * The directory containing stub files for generating new files.
     */
    protected string $stubsDir = __DIR__ . '/Stubs/';

    /**
     * The stub generator used to create files from stubs.
     */
    protected StubGeneratorFactory $stubGenerator;

    /**
     * GeneratorCommand constructor.
     *
     * Initializes the command with necessary dependencies for application state,
     * output styling, input options, and reflection-based function handling.
     *
     * @param  State  $state  The application's state object.
     * @param  StubGeneratorFactory  $stubGenerator  Factory to generate files from stubs.
     * @param  OutputStyleFactory  $outputStyleFactory  Factory to create OutputStyle instances.
     * @param  InputOptionFactory  $inputOptionFactory  Factory to create InputOption instances.
     * @param  ReflectionFunctionFactory  $reflectionFunctionFactory  Factory for creating reflection functions.
     */
    public function __construct(
        State $state,
        StubGeneratorFactory $stubGenerator,
        OutputStyleFactory $outputStyleFactory,
        InputOptionFactory $inputOptionFactory,
        ReflectionFunctionFactory $reflectionFunctionFactory,
    ) {
        parent::__construct(
            $state,
            $outputStyleFactory,
            $inputOptionFactory,
            $reflectionFunctionFactory,
        );

        $this->stubGenerator = $stubGenerator;
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
    abstract protected function getPlaceholders(DataObject $arguments): array;

    /**
     * Create the module's starter files from stubs.
     *
     * This method generates the essential files for the module by rendering predefined stubs
     * with the module's namespace, name, and command. The stub is rendered with the appropriate
     * variables, and the file is saved to the specified path.
     *
     * @param  string|null  $targetFilePath  The path where the generated file will be saved. If not provided, it will be generated.
     * @param  string  $stubName  The name of the stub file to be used for generating the command file.
     */
    protected function generateFile(?string $targetFilePath, string $stubName): void
    {
        try {
            // Get the arguments for the command (namespace, module, command)
            $arguments = $this->arguments();

            // Get the render variables to replace in the stub
            $placeholders = $this->getPlaceholders($arguments);

            // If the target file path is not provided, generate it based on the arguments
            $targetFilePath ??= $this->getFilePath($arguments);

            // Generate the file using the stub template and render with the variables
            $this->stubGenerator->create([
                'source' => $this->stubsDir . $stubName . '.stub', // Path to the stub template
                'target' => $targetFilePath, // Path to save the generated file
            ])->render($placeholders); // Render the file with the variables
        } catch (Exception $e) {
            // Re-throw the exception to ensure the error is handled upstream
            throw $e;
        }
    }
}
