<?php

declare(strict_types=1);

namespace Maginium\Framework\Console;

use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Support\Facades\Filesystem;
use Maginium\Framework\Support\Str;

/**
 * Class StubGenerator.
 *
 * Generates files from stub templates by replacing placeholders with provided values.
 */
class StubGenerator
{
    /**
     * @var string Path to the source stub file.
     */
    protected string $source;

    /**
     * @var string Path to the target file that will be generated.
     */
    protected string $target;

    /**
     * StubGenerator constructor.
     *
     * Initializes the generator with source and target paths.
     *
     * @param  string  $source  Path to the stub template file.
     * @param  string  $target  Path where the generated file will be saved.
     */
    public function __construct(string $source, string $target)
    {
        $this->source = $source;
        $this->target = $target;
    }

    /**
     * Generates a file by replacing placeholders in the stub with provided values.
     *
     * @param  array<string, string>  $replacements  Key-value pairs for placeholder tags and their replacements.
     *
     * @throws RuntimeException If the target file already exists or if there are issues reading or writing files.
     */
    public function render(array $replacements): void
    {
        // Check if the target file already exists to avoid overwriting it.
        if (Filesystem::exists($this->target)) {
            throw RuntimeException::make(sprintf(
                'Cannot generate file. Target "%s" already exists.',
                $this->target,
            ));
        }

        // Load the contents of the stub file.
        $contents = Filesystem::get($this->source);

        if ($contents === false) {
            throw RuntimeException::make(sprintf(
                'Failed to read source stub file: "%s".',
                $this->source,
            ));
        }

        // Perform placeholder replacements.
        foreach ($replacements as $tag => $replacement) {
            // Validate that the replacement is not null before performing the replacement
            if ($replacement !== null) {
                $contents = Str::replace($tag, $replacement, $contents);
            }
        }

        // Ensure the directory for the target file exists; create it if necessary.
        $targetDirectory = Filesystem::dirname($this->target);

        if (! Filesystem::exists($targetDirectory) && ! Filesystem::makeDirectory($targetDirectory) && ! Filesystem::isDirectory($targetDirectory)) {
            throw RuntimeException::make(Str::format(
                'Failed to create directory: "%1".',
                $targetDirectory,
            ));
        }

        // Write the updated contents to the target file.
        if (Filesystem::put($this->target, $contents) === false) {
            throw RuntimeException::make(Str::format(
                'Failed to write to target file: "%1".',
                $this->target,
            ));
        }
    }
}
