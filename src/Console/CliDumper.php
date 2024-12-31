<?php

declare(strict_types=1);

namespace Maginium\Framework\Console;

use Maginium\Foundation\Concerns\ResolvesDumpSource;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Container;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper as BaseCliDumper;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Class CliDumper.
 *
 * This class extends Symfony's BaseCliDumper to provide enhanced CLI debugging capabilities.
 * It adds functionality to include the source file and line number information for dumped variables.
 */
class CliDumper extends BaseCliDumper
{
    // Includes functionality for resolving the dump source (e.g., file and line number)
    use ResolvesDumpSource;

    /**
     * The base path of the application.
     * This is typically the root directory of the application.
     */
    protected string $basePath;

    /**
     * The output instance for CLI output.
     * Used to write the dump output to the console.
     */
    protected ConsoleOutputInterface $output;

    /**
     * Flag to indicate whether the dumper is currently dumping.
     * Prevents multiple dumps from occurring simultaneously.
     */
    protected bool $dumping = false;

    /**
     * Create a new CLI dumper instance.
     *
     * @param  ConsoleOutputInterface  $output  The output instance for CLI.
     * @param  VarCloner  $cloner  The VarCloner instance for cloning variables.
     * @param  string  $basePath  The base path of the application.
     */
    public function __construct(ConsoleOutputInterface $output, string $basePath)
    {
        $this->output = $output;
        $this->basePath = $basePath;

        // Call the parent constructor to initialize the base dumper
        parent::__construct();

        // Set the colors for output based on the environment
        // Determine if color output is supported and apply it
        $this->setColors($this->supportsColors());
    }

    /**
     * Register a new CLI dumper instance and set it as the default dumper.
     *
     * @param  string  $basePath  The base path of the application.
     */
    public static function register(string $basePath)
    {
        // Create a VarCloner instance and add casters for closure file info
        /** @var ClonerInterface $cloner */
        $cloner = tap(Container::resolve(className: VarCloner::class))->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

        // Instantiate the dumper using the Container to handle dependency injection
        // The Container::make() method resolves the dumper and injects the basePath
        $dumper = Container::make(static::class, ['basePath' => $basePath]);

        // Set the handler for VarDumper, which calls the dumpWithSource method when dumping a variable
        VarDumper::setHandler(fn($value) => $dumper->dumpWithSource($cloner->cloneVar($value)));
    }

    /**
     * Dump a variable with its source file and line number.
     *
     * @param  Data  $data  The data to dump, which has been cloned.
     */
    public function dumpWithSource(Data $data)
    {
        // Avoid dumping multiple times if already dumping
        // If the dumper is already processing, skip this dump
        if ($this->dumping) {
            // Proceed with the dump if already dumping
            $this->dump($data);

            return;
        }

        // Set the dumping flag to true
        $this->dumping = true;

        // Capture the output of the dump (in string format) and split by lines
        $output = (string)$this->dump($data, true);
        $lines = explode("\n", $output);

        // Append the source content (file and line info) to the last line of the dump
        $lines[Arr::keyLast($lines) - 1] .= $this->getDumpSourceContent();

        // Write the final output to the CLI output instance
        $this->output->write(implode("\n", $lines));

        // Reset the dumping flag to false
        $this->dumping = false;
    }

    /**
     * Get the HTML source content for the dump.
     *
     * @return string The HTML content for the source file and line number.
     */
    protected function getDumpSourceContent(): string
    {
        // Resolve the dump source information (file and line)
        // If no source is found, return an empty string
        if (null === ($dumpSource = $this->resolveDumpSource())) {
            return '';
        }

        // Extract the file, relative file, and line number from the source information
        [$file, $relativeFile, $line] = $dumpSource;

        // Generate a hyperlink to the source file if available
        $href = $this->resolveSourceHref($file, $line);

        // Return a formatted string that includes the file and line number as a link
        return sprintf(
            ' <fg=gray>// <fg=gray%s>%s%s</></>',
            $href === null ? '' : ";href={$href}",
            $relativeFile,
            $line === null ? '' : ":{$line}",
        );
    }

    /**
     * Determine if the output supports color formatting.
     *
     * @return bool True if the output supports color, false otherwise.
     */
    protected function supportsColors(): bool
    {
        // Check if the CLI output instance supports decorated output (colors)
        return $this->output->isDecorated();
    }
}
