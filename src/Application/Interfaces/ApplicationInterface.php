<?php

declare(strict_types=1);

namespace Maginium\Framework\Application\Interfaces;

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\MaintenanceMode;
use Maginium\Framework\Container\Interfaces\ContainerInterface;

/**
 * A bootstrap of Magento application.
 *
 * Performs basic initialization root function: injects init parameters and creates object manager
 * Can create/run applications.
 */
interface ApplicationInterface extends ContainerInterface
{
    /**
     * Get the path to the environment file directory.
     *
     * @return string
     */
    public function environmentPath(): string;

    /**
     * Get the environment file the application is using.
     *
     * @return string
     */
    public function environmentFile(): string;

    /**
     * Get the fully qualified path to the environment file.
     *
     * @return string
     */
    public function environmentFilePath(): string;

    /**
     * Get an instance of the maintenance mode manager implementation.
     *
     * @return MaintenanceMode
     */
    public function maintenanceMode(): MaintenanceMode;

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function isDownForMaintenance(): bool;

    /**
     * Get or check the current application environment.
     *
     * @param  string|array  ...$environments
     *
     * @return string|bool
     */
    public function environment(...$environments): string|bool;

    /**
     * Determine if the application is in the local environment.
     *
     * @return bool
     */
    public function isLocal(): bool;

    /**
     * Determine if the application is in the production environment.
     *
     * @return bool
     */
    public function isProduction(): bool;
}
