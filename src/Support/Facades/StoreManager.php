<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the store manager.
 *
 * @method static void setIsSingleStoreModeAllowed(bool $value)
 *     Allow or disallow single store mode.
 *     Parameters:
 *     - $value: Boolean value to allow or disallow single store mode.
 *     Returns: void
 * @method static bool hasSingleStore()
 *     Check if store has only one store view.
 *     Returns:
 *     - bool: True if store has only one store view, false otherwise.
 * @method static bool isSingleStoreMode()
 *     Check if system is run in the single store mode.
 *     Returns:
 *     - bool: True if system is in single store mode, false otherwise.
 * @method static StoreInterface getStore(null|string|bool|int|StoreInterface $storeId = null)
 *     Retrieve application store object by store ID or other criteria.
 *     Parameters:
 *     - $storeId: Store ID, store code, boolean value, integer value, or StoreInterface object (optional).
 *     Returns:
 *     - StoreInterface|null: The retrieved store object or null if not found.
 * @method static StoreInterface[] getStores(bool $withDefault = false, bool $codeKey = false)
 *     Retrieve array of store objects.
 *     Parameters:
 *     - $withDefault: Whether to include default store (optional, default is false).
 *     - $codeKey: Whether to use store code as array key (optional, default is false).
 *     Returns:
 *     - StoreInterface[]: Array of StoreInterface objects.
 * @method static WebsiteInterface getWebsite(null|bool|int|string|WebsiteInterface $websiteId = null)
 *     Retrieve application website object by website ID or other criteria.
 *     Parameters:
 *     - $websiteId: Website ID, boolean value, integer value, string value, or WebsiteInterface object (optional).
 *     Returns:
 *     - WebsiteInterface|null: The retrieved website object or null if not found.
 * @method static WebsiteInterface[] getWebsites(bool $withDefault = false, bool $codeKey = false)
 *     Retrieve array of website objects.
 *     Parameters:
 *     - $withDefault: Whether to include default website (optional, default is false).
 *     - $codeKey: Whether to use website code as array key (optional, default is false).
 *     Returns:
 *     - WebsiteInterface[]: Array of WebsiteInterface objects.
 * @method static void reinitStores()
 *     Reinitialize store list.
 *     Returns: void
 * @method static StoreInterface|null getDefaultStoreView()
 *     Retrieve default store view for default group and website.
 *     Returns:
 *     - StoreInterface|null: The default store view or null if not found.
 * @method static GroupInterface getGroup(null|GroupInterface|string $groupId = null)
 *     Retrieve application store group object by group ID or other criteria.
 *     Parameters:
 *     - $groupId: Group ID, GroupInterface object, or string (optional).
 *     Returns:
 *     - GroupInterface|null: The retrieved store group object or null if not found.
 * @method static GroupInterface[] getGroups(bool $withDefault = false)
 *     Prepare array of store groups.
 *     Parameters:
 *     - $withDefault: Whether to include default group (optional, default is false).
 *     Returns:
 *     - GroupInterface[]: Array of GroupInterface objects.
 * @method static void setCurrentStore(string|int|StoreInterface $store)
 *     Set current default store.
 *     Parameters:
 *     - $store: Store ID, integer value, string value, or StoreInterface object.
 *     Returns: void
 *
 * @see StoreManagerInterface
 */
class StoreManager extends Facade
{
    /**
     * Indicates whether resolved instances should be cached.
     *
     * @var bool
     */
    protected static $cached = false;

    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return StoreManagerInterface::class;
    }
}
