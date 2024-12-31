<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Actions\ActionManager;
use Maginium\Framework\Support\Facade;

/**
 * @method static void registerRoutes($paths = 'app/Actions')
 * @method static void registerCommands($paths = 'app/Actions')
 *
 * @see ActionManager
 */
class Actions extends Facade
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
        return ActionManager::class;
    }
}
