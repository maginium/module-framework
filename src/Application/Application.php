<?php

declare(strict_types=1);

namespace Maginium\Framework\Application;

use Illuminate\Support\ProcessUtils;
use Magento\Framework\App\MaintenanceMode;
use Maginium\Framework\Application\Interfaces\ApplicationInterface;
use Maginium\Framework\Container\ContainerManager;
use Maginium\Framework\Support\Str;

/**
 * A bootstrap of Magento application.
 *
 * Performs basic initialization root function: injects init parameters and creates object manager
 * Can create/run applications
 */
class Application extends ContainerManager implements ApplicationInterface
{
    /**
     * The default path to the Magento binary.
     */
    public const DEFAULT_MAGENTO_BINARY = 'bin/magento';

    /**
     * The base path for the Laravel installation.
     *
     * @var string
     */
    protected $basePath = BP;

    /**
     * The custom environment path defined by the developer.
     *
     * @var string
     */
    protected $environmentPath;

    /**
     * The environment file to load during bootstrapping.
     *
     * @var string
     */
    protected $environmentFile = '.env';

    /**
     * Format the given command as a fully-qualified executable command.
     *
     * @param  string  $string
     *
     * @return string
     */
    public static function formatCommandString($string): string
    {
        return Str::format('%s %s %s', static::phpBinary(), static::magentoBinary(), $string);
    }

    /**
     * Get the PHP binary path.
     *
     * This method utilizes the PhpExecutableFinder to locate the PHP binary
     * available in the system.
     *
     * @return string The path to the PHP binary.
     */
    public static function phpBinary(): string
    {
        // Use PhpExecutableFinder to locate the PHP binary or default to the constant value.
        return ProcessUtils::escapeArgument(php_binary());
    }

    /**
     * Get the Magento binary path.
     *
     * This method checks if the `MAGENTO_BINARY` constant is defined. If not, it defaults to `DEFAULT_MAGENTO_BINARY`.
     *
     * @return string The path to the Magento binary.
     */
    public static function magentoBinary(): string
    {
        // Return the defined MAGENTO_BINARY constant or default to the constant value.
        return ProcessUtils::escapeArgument(defined('MAGENTO_BINARY') ? MAGENTO_BINARY : self::DEFAULT_MAGENTO_BINARY);
    }

    /**
     * Get the path to the environment file directory.
     *
     * @return string
     */
    public function environmentPath(): string
    {
        return $this->environmentPath ?: $this->basePath;
    }

    /**
     * Get the environment file the application is using.
     *
     * @return string
     */
    public function environmentFile(): string
    {
        return $this->environmentFile ?: '.env';
    }

    /**
     * Get the fully qualified path to the environment file.
     *
     * @return string
     */
    public function environmentFilePath(): string
    {
        return $this->environmentPath() . DIRECTORY_SEPARATOR . $this->environmentFile();
    }

    /**
     * Get an instance of the maintenance mode manager implementation.
     *
     * @return MaintenanceMode
     */
    public function maintenanceMode(): MaintenanceMode
    {
        return $this->make(MaintenanceMode::class);
    }

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function isDownForMaintenance(): bool
    {
        return $this->maintenanceMode()->isOn();
    }

    /**
     * Get or check the current application environment.
     *
     * @param  string|array  ...$environments
     *
     * @return string|bool
     */
    public function environment(...$environments): string|bool
    {
        if (count($environments) > 0) {
            $patterns = is_array($environments[0]) ? $environments[0] : $environments;

            return Str::is($patterns, env('app.env'));
        }

        return env('app.env');
    }

    /**
     * Determine if the application is in the local environment.
     *
     * @return bool
     */
    public function isLocal(): bool
    {
        return env('app.env') === 'local';
    }

    /**
     * Determine if the application is in the production environment.
     *
     * @return bool
     */
    public function isProduction(): bool
    {
        return env('app.env') === 'production';
    }
}
