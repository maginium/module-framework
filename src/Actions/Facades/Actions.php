<?php

declare(strict_types=1);

namespace Maginium\Framework\Actions\Facades;

use Maginium\Framework\Actions\ActionManager;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the ActionManager service.
 *
 * @method static void registerRoutes($paths = 'app/Actions') Registers routes for the actions.
 * @method static self setBacktraceLimit(int $backtraceLimit) Sets the backtrace limit for identifying design patterns.
 * @method static self setDesignPatterns(array $designPatterns) Sets the design patterns to be managed by the ActionManager.
 * @method static array getDesignPatterns() Gets all the design patterns currently managed by the ActionManager.
 * @method static self registerDesignPattern(\Maginium\Framework\Actions\DesignPattern $designPattern) Registers a new design pattern with the ActionManager.
 * @method static array getDesignPatternsMatching(array $usedTraits) Gets all design patterns that match the used traits of the provided action.
 * @method static ?\Maginium\Framework\Actions\DesignPattern identifyFromBacktrace(array $usedTraits, ?\Maginium\Framework\Actions\BacktraceFrame &$frame = null) Identifies the design pattern from the backtrace, matching the used traits.
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
