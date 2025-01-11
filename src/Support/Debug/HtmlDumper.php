<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Debug;

use Maginium\Foundation\Concerns\ResolvesDumpSource;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Str;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper as BaseHtmlDumper;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Class HtmlDumper.
 *
 * This class extends Symfony's HtmlDumper to include source file and line information
 * in variable dumps for improved debugging.
 */
class HtmlDumper extends BaseHtmlDumper
{
    // Use trait for resolving the source of dumps.
    use ResolvesDumpSource;

    /**
     * Separator for the source in expanded dumps.
     * Helps identify and append source information for expanded views.
     *
     * @var string
     */
    public const EXPANDED_SEPARATOR = 'class=sf-dump-expanded>';

    /**
     * Separator for the source in non-expanded dumps.
     * Used for inserting source details in collapsed views.
     *
     * @var string
     */
    public const NON_EXPANDED_SEPARATOR = "\n</pre><script>";

    /**
     * The base path of the application.
     * Used for constructing relative file paths in dumps.
     *
     * @var string
     */
    protected $basePath;

    /**
     * A flag indicating whether a variable is being dumped.
     * Prevents recursive calls during dumping.
     *
     * @var bool
     */
    protected $dumping = false;

    /**
     * @var array styles definition for output
     */
    protected array $styles = [
        'cchr' => 'color:#222',
        'num' => 'color:#a71d5d',
        'ref' => 'color:#a0a0a0',
        'key' => 'color:#df5000',
        'str' => 'color:#df5000',
        'meta' => 'color:#b729d9',
        'note' => 'color:#a71d5d',
        'const' => 'color:#795da3',
        'index' => 'color:#a71d5d',
        'public' => 'color:#795da3',
        'private' => 'color:#795da3',
        'protected' => 'color:#795da3',
        'default' => 'background-color:#fff; color:#222; line-height:1.2em; font-weight:normal; font:12px Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:100000',
    ];

    /**
     * HtmlDumper constructor.
     *
     * Initializes the dumper with the base path and cloner instance.
     *
     * @param string    $basePath The base path of the application.
     */
    public function __construct(string $basePath)
    {
        // Call the parent constructor to initialize base dumper.
        parent::__construct();

        // Set the base path for relative paths.
        $this->basePath = $basePath;
    }

    /**
     * Registers the dumper as the default handler for variable dumping.
     *
     * Sets up the dumper to handle all var_dump outputs globally.
     *
     * @param string $basePath The base path of the application.
     *
     * @return void
     */
    public static function register($basePath)
    {
        // Configure the cloner to handle reflection-specific casting.
        /** @var ClonerInterface $cloner */
        $cloner = tap(Container::resolve(className: VarCloner::class))->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

        // Create a new dumper instance via the container for dependency injection.
        /** @var HtmlDumper $dumper */
        $dumper = Container::make(static::class, ['basePath' => $basePath]);

        // Override the default VarDumper handler to use this dumper.
        VarDumper::setHandler(fn($value) => $dumper->dumpWithSource($cloner->cloneVar($value)));
    }

    /**
     * Dumps a variable with source file and line number information.
     *
     * Enhances the output with source details for easier debugging.
     *
     * @param Data $data The variable data to be dumped.
     *
     * @return void
     */
    public function dumpWithSource(Data $data)
    {
        // Check if the dumper is already in use to avoid recursion.
        if ($this->dumping) {
            // Perform a standard dump if already dumping.
            $this->dump($data);

            return;
        }

        // Mark the start of a dump process.
        $this->dumping = true;

        // Convert the data to a dump output string.
        $output = (string)$this->dump($data, true);

        // Modify the output based on the dump's expansion state.
        $output = match (true) {
            str_contains($output, static::EXPANDED_SEPARATOR) => str_replace(
                static::EXPANDED_SEPARATOR,
                static::EXPANDED_SEPARATOR . $this->getDumpSourceContent(), // Append source content.
                $output,
            ),
            str_contains($output, static::NON_EXPANDED_SEPARATOR) => str_replace(
                static::NON_EXPANDED_SEPARATOR,
                $this->getDumpSourceContent() . static::NON_EXPANDED_SEPARATOR, // Insert source content.
                $output,
            ),
            default => $output, // Keep output unchanged if no match is found.
        };

        // Write the final output to the stream.
        fwrite($this->outputStream, $output);

        // Reset the dumping flag after completion.
        $this->dumping = false;
    }

    /**
     * Retrieves formatted HTML content of the dump's source file and line number.
     *
     * Provides a clickable link or plain text for the dump's location.
     *
     * @return string The HTML representation of the dump's source or an empty string.
     */
    protected function getDumpSourceContent()
    {
        // Attempt to resolve the dump source using the trait method.
        if (null === ($dumpSource = $this->resolveDumpSource())) {
            // Return an empty string if no source is found.
            return '';
        }

        // Extract file path, relative path, and line number from the resolved source.
        [$file, $relativeFile, $line] = $dumpSource;

        // Format the source path and line number as a string.
        $source = Str::format('%s%s', $relativeFile, $line === null ? '' : ":{$line}");

        // Create a clickable HTML link if a source URL can be resolved.
        if ($href = $this->resolveSourceHref($file, $line)) {
            // Wrap source in an anchor tag.
            $source = Str::format('<a href="%s">%s</a>', $href, $source);
        }

        // Return the formatted source string styled in gray color.
        return Str::format('<span style="color: #A0A0A0;"> // %s</span>', $source);
    }
}
