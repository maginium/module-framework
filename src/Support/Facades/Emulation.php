<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Store\Model\App\Emulation as EmulationManager;
use Maginium\Framework\Support\Facade;

/**
 * Class Emulation.
 *
 * Facade for interacting with store environment emulation services.
 * Provides methods to start and stop emulation for a specific store's environment,
 * such as translating settings, store-specific configurations, and other contextual data.
 *
 * @method static void startEnvironmentEmulation(int $storeId,string $area = \Magento\Framework\App\Area::AREA_FRONTEND,bool $force = false)
 *     Start environment emulation for the specified store.
 *     Parameters:
 *     - int $storeId: The ID of the store to emulate.
 *     - string $area: The area to emulate, default is AREA_FRONTEND.
 *     - bool $force: Whether to force the emulation, default is false.
 *     Returns:
 *     - void
 * @method static void stopEnvironmentEmulation()
 *     Stop environment emulation and restore the previous state.
 *     Returns:
 *     - void
 * @method static void storeCurrentEnvironmentInfo()
 *     Store the current environment information, including translation settings and other context-specific data.
 *     Returns:
 *     - void
 *
 * @see EmulationManager
 */
class Emulation extends Facade
{
    /**
     * Indicates if the resolved facade should be cached.
     *
     * @var bool
     */
    protected static $cached = false;

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
        return EmulationManager::class;
    }
}
