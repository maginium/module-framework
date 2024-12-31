<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Framework\Setup\SampleData\FixtureManager as BaseFixtureManager;
use Maginium\Framework\Support\Facade;

/**
 * FixtureManager facade class to interact with the BaseFixtureManager.
 *
 * This facade simplifies the interaction with the underlying FixtureManager class,
 * providing access to its functionality such as retrieving fixtures by file ID.
 *
 * @method static void refreshEventDispatcher()
 * @method static mixed getFixture($fileId)
 *
 * @see BaseFixtureManager
 */
class FixtureManager extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade, which in this case is the BaseFixtureManager.
     */
    protected static function getAccessor(): string
    {
        return BaseFixtureManager::class;
    }
}
