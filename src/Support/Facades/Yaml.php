<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Parse\Yaml as ParseYaml;
use Maginium\Framework\Support\Facade;

/**
 * Yaml.
 *
 * @method static array parse(string $contents)
 * @method static array parseFile(string $fileName)
 * @method static array parseFileCached(string $fileName)
 * @method static string render(array $vars, array $options = [])
 * @method static array toArray(DOMNode $node)
 *
 * @see ParseYaml
 */
class Yaml extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    protected static function getAccessor(): string
    {
        return ParseYaml::class;
    }
}