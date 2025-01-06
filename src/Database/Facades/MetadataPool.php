<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Facades;

use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool as MagentoMetadataPool;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the MetadataPool service.
 *
 * This facade provides access to the MetadataPool class in Magento's framework,
 * which manages entity metadata, configuration, and hydrators.
 *
 * @method static EntityMetadataInterface getMetadata(string $entityType) Retrieve metadata for a specific entity type.
 * @method static \Magento\Framework\EntityManager\HydratorInterface getHydrator(string $entityType) Deprecated. Get the hydrator for a specific entity type.
 * @method static bool hasConfiguration(string $entityType) Check if metadata configuration exists for a specific entity type.
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
