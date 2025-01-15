<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail;

use Maginium\Framework\Component\Module;
use Maginium\Framework\Mail\Interfaces\RendererInterface;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Path;
use Maginium\Framework\Support\Reflection;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessFactory;

/**
 * Class Renderer.
 *
 * Responsible for rendering React-based email templates by invoking a Node.js process.
 * It utilizes the `tsx` executable to render templates written in TypeScript.
 *
 * This class abstracts the process of calling a Node.js executable for rendering,
 * making it easier to handle email templates written in React.
 */
class Renderer implements RendererInterface
{
    /**
     * The Process instance responsible for running the Node.js command.
     *
     * @var ProcessFactory
     */
    private ProcessFactory $processFactory;

    /**
     * Renderer constructor.
     *
     * Initializes the process to render a React email template using the `tsx` executable.
     * This constructor is private to ensure that rendering is done through the static `render` method.
     *
     * @param ProcessFactory $processFactory The Process instance responsible for running the Node.js command.
     *
     * @throws NodeNotFoundException If Node.js executable cannot be resolved.
     */
    public function __construct(
        ProcessFactory $processFactory,
    ) {
        $this->processFactory = $processFactory;
    }

    /**
     * Renders a React-based email template and returns the output as an array.
     *
     * This static method runs the Node.js process, handles any potential errors,
     * and decodes the rendered output into an associative array.
     *
     * @param string $view The name of the React component to render.
     * @param array $data Data to pass as props to the component.
     *
     * @throws NodeNotFoundException If the Node.js executable is not found.
     * @throws ProcessFailedException If the rendering process fails.
     *
     * @return DataObject The rendered template data.
     */
    public function render(string $view, array $data): DataObject
    {
        // Create and run the process for rendering
        $process = $this->create($view, $data);
        $process->run();

        // Check if the process was successful
        if (! $process->isSuccessful()) {
            // Log the error and throw an exception if the process failed
            // You might want to replace dd with error logging in production
            error_log('Process failed with output: ' . $process->getOutput());

            throw new ProcessFailedException($process);
        }

        // Decode the JSON output from the process
        $results = Json::decode($process->getOutput());

        // Return the results as DataObject instance
        return DataObject::make($results);
    }

    /**
     * Factory method to create the process instance for rendering the email template.
     *
     * This method constructs the necessary arguments for the Node.js process and returns a fully configured Process.
     *
     * @param string $view Name of the email template to render.
     * @param array $data Data to pass as props to the React component.
     *
     * @return Process The configured Process instance for rendering.
     */
    private function create(string $view, array $data): Process
    {
        // Get the arguments required for the Node.js process
        $command = $this->getProcessArguments($view, $data);

        // Create and return the Process instance
        return $this->processFactory->create(['command' => $command]);
    }

    /**
     * Get the arguments for the process to render the email template.
     *
     * This method organizes the arguments required for the `tsx` executable to run the rendering.
     *
     * @param string $view Name of the email template to render.
     * @param array $data Data to pass as props to the React component.
     *
     * @return array The arguments to pass to the process.
     */
    private function getProcessArguments(string $view, array $data): array
    {
        // Resolve the Node.js executable path, used to run the rendering process
        $nodeExecutable = node_binary();

        // Path to the 'tsx' executable in the 'node_modules' directory, responsible for rendering TypeScript files
        $tsxPath = tsx_binary();

        // Obtain the module name from the namespace of the current class.
        $moduleName = Reflection::getNamespaceName(static::class, 2);

        // Resolve the path to the module, this will be used to locate the render script
        $modulePath = Module::getPath($moduleName);

        // Build the full path to the render script 'main.ts' within the module
        $renderScriptPath = Path::join($modulePath, 'Mail', 'view', 'base', 'js', 'renderer', 'main.ts');

        // Define the directory where email templates are stored; this can be dynamic if needed
        $viewPath = Path::join($view);  // Hardcoded as root for now, but it could be set via config

        // Encode the data array into a JSON string to pass it as props to the React component
        $encodedData = Json::encode($data);

        // Return the complete set of arguments required for the process
        return [
            $nodeExecutable, // The Node.js executable path
            $tsxPath, // The path to the 'tsx' executable
            $renderScriptPath, // Path to the render script responsible for rendering the email template
            $viewPath,  // Full path to the email template, appended with the view name
            $encodedData, // JSON-encoded data to be passed to the React component
        ];
    }
}
