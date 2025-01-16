<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Mail\Interfaces\RendererInterface;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Renderer service.
 *
 * This facade provides a simplified interface for rendering React-based email templates by invoking a Node.js process.
 *
 * @method static DataObject render(string $view, array $data) Renders a React-based email template and returns the output as an array.
 *
 * @see RendererInterface
 */
class Renderer extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return RendererInterface::class;
    }
}
