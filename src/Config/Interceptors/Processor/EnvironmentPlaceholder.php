<?php

declare(strict_types=1);

namespace Maginium\Framework\Config\Interceptors\Processor;

use Magento\Config\Model\Config\Processor\EnvironmentPlaceholder as BaseEnvironmentPlaceholder;
use Maginium\Framework\Config\EnvConfigLoader;

/**
 * Class EnvironmentPlaceholder.
 *
 * This class is responsible for managing the loading and setting of environment variables
 * prior to application launch. It intercepts the bootstrapping process to ensure all necessary
 * environment configurations are properly loaded.
 */
class EnvironmentPlaceholder
{
    /**
     * Before plugin for the process method of EnvironmentPlaceholder.
     */
    public function beforeProcess(BaseEnvironmentPlaceholder $subject, array $config): void
    {
        // Load the environment variables before the application starts.
        EnvConfigLoader::load();
    }
}
