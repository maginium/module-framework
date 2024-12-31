<?php

declare(strict_types=1);

namespace Maginium\Framework\Config\Interceptors\App;

use Closure;
use Magento\Framework\App\Http as AppHttp;
use Maginium\Framework\Config\EnvConfigLoader;

/**
 * Class Http.
 *
 * This class provides a method to load environment variables before processing.
 * It acts as a interceptor for pre-processing tasks, ensuring that environment variables
 * are loaded and available for the subsequent processes.
 */
class Http
{
    /**
     * Load environment variables before launching the application.
     *
     * This method is used as a interceptor for the `aroundLaunch` method in `AppHttp` class.
     * It ensures that environment variables are loaded and available for the subsequent processes.
     *
     * @param  AppHttp  $subject  The subject being observed.
     * @param  Closure  $proceed  The original method that is being intercepted.
     *
     * @return mixed The result of the original method.
     */
    public function aroundLaunch(AppHttp $subject, Closure $proceed)
    {
        EnvConfigLoader::load();

        // Proceed with the original method.
        return $proceed();
    }
}
