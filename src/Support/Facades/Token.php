<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Support\Facade;
use Maginium\Framework\Token\Services\TokenManager;

/**
 * Class Token.
 *
 * Facade for interacting with the Token management services.
 *
 * Provides access to customer, admin, and API key token management services.
 *
 * @method static \Maginium\Framework\Token\Interfaces\ApiKeyServiceInterface apiKey() Returns the service responsible for managing API keys.
 * @method static \Maginium\Framework\Token\Interfaces\AdminTokenServiceInterface admin() Returns the service responsible for managing admin tokens.
 * @method static \Maginium\Framework\Token\Interfaces\CustomerTokenServiceInterface customer() Returns the service responsible for managing customer tokens.
 *
 * @see TokenManager
 */
class Token extends Facade
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
        return TokenManager::class;
    }
}
