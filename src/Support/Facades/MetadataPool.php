<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Framework\EntityManager\MetadataPool as MagentoMetadataPool;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Config service.
 *
 * @see MagentoMetadataPool
 */
class MetadataPool extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return MagentoMetadataPool::class;
    }
}
