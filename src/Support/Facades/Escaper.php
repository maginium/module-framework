<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Framework\Escaper as EscaperManager;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Magento Escaper service.
 *
 * This class provides a simplified interface to access the `EscaperManager` methods,
 * such as escaping HTML and HTML attributes. The facade allows these methods to be used
 * without directly interacting with the underlying EscaperManager class.
 *
 * @method static string escapeHtml(string $data, string|null $allowedTags = null) Escapes HTML tags in the given string. Optionally allows certain tags.
 * @method static string escapeHtmlAttr(string $string, bool $escapeSingleQuote = true) Escapes HTML attribute values, ensuring quotes are properly handled.
 *
 * @see EscaperManager
 */
class Escaper extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return EscaperManager::class;
    }
}
