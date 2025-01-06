<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Eav\Model\Config as ConfigInterface;
use Maginium\Framework\Support\Facade;

/**
 * EavConfig facade for accessing EAV configuration methods.
 *
 * @method static \Magento\Eav\Model\Entity\Attribute\AbstractAttribute getAttribute(string $entityType, string $attributeCode)
 * @method static \Magento\Eav\Model\Config getEntityType(string $entityTypeCode)
 * @method static \Magento\Eav\Api\AttributeRepositoryInterface getAttributeRepository()
 * @method static \Magento\Eav\Model\Entity\Type getEntityTypeByCode(string $entityTypeCode)
 * @method static \Magento\Eav\Model\Entity\Type getEntityTypeById(int $entityTypeId)
 * @method static \Magento\Eav\Model\Attribute\Source\AbstractSource getSourceModel(string $entityType, string $attributeCode)
 * @method static \Magento\Eav\Api\AttributeSetRepositoryInterface getAttributeSetRepository()
 * @method static void clearCache()
 * @method static bool isAttributeSetValid(int $attributeSetId, string $entityType)
 * @method static void createAttributeSet(string $entityTypeCode, string $attributeSetName, int $attributeSetId)
 * @method static \Magento\Eav\Model\Attribute\Backend\Boolean getBooleanAttribute(string $entityType, string $attributeCode)
 * @method static \Magento\Eav\Model\Attribute\Backend\Datetime getDatetimeAttribute(string $entityType, string $attributeCode)
 * @method static \Magento\Eav\Model\Attribute\Backend\Decimal getDecimalAttribute(string $entityType, string $attributeCode)
 * @method static \Magento\Eav\Model\Attribute\Backend\Integer getIntegerAttribute(string $entityType, string $attributeCode)
 * @method static \Magento\Eav\Model\Attribute\Backend\Text getTextAttribute(string $entityType, string $attributeCode)
 * @method static \Magento\Eav\Model\Attribute\Backend\Varchar getVarcharAttribute(string $entityType, string $attributeCode)
 * @method static \Magento\Eav\Model\Attribute\Backend\Date getDateAttribute(string $entityType, string $attributeCode)
 *
 * @see ConfigInterface
 */
class EavConfig extends Facade
{
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
        return ConfigInterface::class;
    }
}
