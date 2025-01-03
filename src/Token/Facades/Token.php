<?php

declare(strict_types=1);

namespace Pixicommerce\Framework\Token\Facades;

use Pixicommerce\Framework\Support\Facade;
use Pixicommerce\Framework\Token\Services\TokenManager;

/**
 * Class Token.
 *
 * Facade for interacting with the Token management services.
 *
 * Provides access to customer, admin, and API key token management services.
 *
 * @method static CustomerTokenServiceInterface customer() Returns the service responsible for managing customer tokens.
 * @method static AdminTokenServiceInterface admin() Returns the service responsible for managing admin tokens.
 * @method static ApiKeyServiceInterface apiKey() Returns the service responsible for managing API keys.
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
