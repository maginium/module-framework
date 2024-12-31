<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppStateManager;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Support\Facade;

/**
 * Facade for managing Magento's application state.
 *
 * Provides static methods to set and retrieve application states for different areas
 * such as frontend, adminhtml, crontab, etc., using Magento's application state manager.
 *
 * @method static void refreshEventDispatcher() Refresh the event dispatcher.
 * @method static string getMode() Retrieve the current application mode.
 * @method static void setIsDownloader(bool $flag = true) Set whether the application is in downloader mode.
 * @method static void setAreaCode(string $code) Set the application area code.
 * @method static string getAreaCode() Retrieve the current application area code.
 * @method static bool isAreaCodeEmulated() Check if the area code is currently emulated.
 * @method static mixed emulateAreaCode(string $areaCode, callable $callback, array $params = []) Emulate an area code and execute a callback within that context.
 * @method static void setGlobal() Set the application state to the global area.
 * @method static void setFrontend() Set the application state to the frontend area.
 * @method static void setAdminhtml() Set the application state to the adminhtml area.
 * @method static void setDoc() Set the application state to the doc area.
 * @method static void setCrontab() Set the application state to the crontab area.
 * @method static void setWebApiRest() Set the application state to the webapi_rest area.
 * @method static void setWebApiSoap() Set the application state to the webapi_soap area.
 * @method static void setGraphQL() Set the application state to the graphql area.
 * @method static bool isAreaSet() Check if the area code is set, returns true if set, false otherwise.
 *
 * @see AppStateManager
 */
class AppState extends Facade
{
    /**
     * Default mode when no specific mode is set.
     */
    public const MODE_DEFAULT = 'default';

    /**
     * Developer mode for debugging and development.
     */
    public const MODE_DEVELOPER = 'developer';

    /**
     * Production mode for live environments.
     */
    public const MODE_PRODUCTION = 'production';

    /**
     * Set the application state to the global area.
     *
     * This method sets the application area code to "global",
     * which is used for operations that are not specific to any other area.
     *
     * @return void
     */
    public static function setGlobal(): void
    {
        static::getFacadeRoot()->setAreaCode(Area::AREA_GLOBAL);
    }

    /**
     * Set the application state to the frontend area.
     *
     * This method sets the application area code to "frontend",
     * typically used for rendering the store's customer-facing interface.
     *
     * @return void
     */
    public static function setFrontend(): void
    {
        static::getFacadeRoot()->setAreaCode(Area::AREA_FRONTEND);
    }

    /**
     * Set the application state to the adminhtml area.
     *
     * This method sets the application area code to "adminhtml",
     * which is used for the Magento Admin Panel.
     *
     * @return void
     */
    public static function setAdminhtml(): void
    {
        static::getFacadeRoot()->setAreaCode(Area::AREA_ADMINHTML);
    }

    /**
     * Set the application state to the doc area.
     *
     * This method sets the application area code to "doc",
     * which might be used for documentation generation or similar processes.
     *
     * @return void
     */
    public static function setDoc(): void
    {
        static::getFacadeRoot()->setAreaCode(Area::AREA_DOC);
    }

    /**
     * Set the application state to the crontab area.
     *
     * This method sets the application area code to "crontab",
     * which is used for cron job executions in Magento.
     *
     * @return void
     */
    public static function setCrontab(): void
    {
        static::getFacadeRoot()->setAreaCode(Area::AREA_CRONTAB);
    }

    /**
     * Set the application state to the webapi_rest area.
     *
     * This method sets the application area code to "webapi_rest",
     * which is used for REST API requests in Magento.
     *
     * @return void
     */
    public static function setWebApiRest(): void
    {
        static::getFacadeRoot()->setAreaCode(Area::AREA_WEBAPI_REST);
    }

    /**
     * Set the application state to the webapi_soap area.
     *
     * This method sets the application area code to "webapi_soap",
     * which is used for SOAP API requests in Magento.
     *
     * @return void
     */
    public static function setWebApiSoap(): void
    {
        static::getFacadeRoot()->setAreaCode(Area::AREA_WEBAPI_SOAP);
    }

    /**
     * Set the application state to the graphql area.
     *
     * This method sets the application area code to "graphql",
     * which is used for handling GraphQL API requests in Magento.
     *
     * @return void
     */
    public static function setGraphQL(): void
    {
        static::getFacadeRoot()->setAreaCode(Area::AREA_GRAPHQL);
    }

    /**
     * Check if area code is set.
     *
     * This method checks whether the area code is set, and handles the exception
     * thrown by getAreaCode if it's not. It returns a boolean value indicating
     * whether the area code is set.
     *
     * @return bool
     */
    public static function isAreaSet(): bool
    {
        try {
            // Attempt to get the area code
            static::getFacadeRoot()->getAreaCode();

            // If no exception is thrown, the area code is set
            return true;
        } catch (LocalizedException $e) {
            // If exception is thrown, return false
            return false;
        }
    }

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
        return AppStateManager::class;
    }
}
