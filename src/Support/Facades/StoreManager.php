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
 * @method static void setIsSingleStoreModeAllowed(bool $value) Allow or disallow single store mode.
 * @method static bool hasSingleStore() Check if store has only one store view.
 * @method static bool isSingleStoreMode() Check if system is run in the single store mode.
 * @method static StoreInterface getStore(null|string|bool|int|StoreInterface $storeId = null) Retrieve application store object by store ID or other criteria. - StoreInterface|null: The retrieved store object or null if not found.
 * @method static StoreInterface[] getStores(bool $withDefault = false, bool $codeKey = false) Retrieve array of store objects.
 * @method static WebsiteInterface getWebsite(null|bool|int|string|WebsiteInterface $websiteId = null) Retrieve application website object by website ID or other criteria.
 * @method static WebsiteInterface[] getWebsites(bool $withDefault = false, bool $codeKey = false) Retrieve array of website objects.
 * @method static void reinitStores() Reinitialize store list.
 * @method static StoreInterface|null getDefaultStoreView() Retrieve default store view for default group and website.
 * @method static GroupInterface getGroup(null|GroupInterface|string $groupId = null) Retrieve application store group object by group ID or other criteria.
 * @method static GroupInterface[] getGroups(bool $withDefault = false) Prepare array of store groups.
 * @method static void setCurrentStore(string|int|StoreInterface $store) Set current default store.
 *
 * @see StoreManagerInterface
 */
class StoreManager extends Facade
{
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
