<?php

declare(strict_types=1);

namespace Maginium\Framework\Swagger\Interceptors;

use Magento\Framework\App\State;
use Magento\Store\Model\ScopeInterface;
use Maginium\Framework\Support\Facades\Config;

/**
 * Class SwaggerConfig.
 *
 * This class is a plugin to modify Swagger configuration.
 */
class SwaggerConfig
{
    private const CONFIG_PATH_ENABLE_SWAGGER = 'dev/swagger/active';

    /**
     * @var State
     */
    protected $state;

    /**
     * SwaggerConfig constructor.
     *
     * @param State $state
     */
    public function __construct(
        State $state,
    ) {
        $this->state = $state;
    }

    /**
     * Enables Swagger based on configuration setting, even in production mode.
     * Always enables Swagger in developer mode.
     *
     * @return bool
     *
     * @see \Magento\Swagger\Model\Config::isEnabled
     */
    public function afterIsEnabled(): bool
    {
        // Enable Swagger if the application is in developer mode
        // or if the configuration flag is set in the store scope.
        return $this->state->getMode() === State::MODE_DEVELOPER
        || Config::getBool(
            self::CONFIG_PATH_ENABLE_SWAGGER,
            ScopeInterface::SCOPE_STORE,
        );
    }
}
