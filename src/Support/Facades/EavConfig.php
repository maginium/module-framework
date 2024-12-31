<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Eav\Model\Config as ConfigInterface;
use Maginium\Framework\Support\Facade;

/**
 * EavConfig facade for accessing EAV configuration methods.
 *
 * @method static \Magento\Eav\Model\Entity\Attribute\AbstractAttribute getAttribute(string $modelType, string $attributeCode)
 * @method static \Magento\Eav\Model\Config getEntityType(string $modelTypeCode)
 * @method static \Magento\Eav\Api\AttributeRepositoryInterface getAttributeRepository()
 * @method static \Magento\Eav\Model\Entity\Type getEntityTypeByCode(string $modelTypeCode)
 * @method static \Magento\Eav\Model\Entity\Type getEntityTypeById(int $modelTypeId)
 * @method static \Magento\Eav\Model\Attribute\Source\AbstractSource getSourceModel(string $modelType, string $attributeCode)
 * @method static \Magento\Eav\Api\AttributeSetRepositoryInterface getAttributeSetRepository()
 * @method static void clearCache()
 * @method static bool isAttributeSetValid(int $attributeSetId, string $modelType)
 * @method static void createAttributeSet(string $modelTypeCode, string $attributeSetName, int $attributeSetId)
 * @method static \Magento\Eav\Model\Attribute\Backend\Boolean getBooleanAttribute(string $modelType, string $attributeCode)
 * @method static \Magento\Eav\Model\Attribute\Backend\Datetime getDatetimeAttribute(string $modelType, string $attributeCode)
 * @method static \Magento\Eav\Model\Attribute\Backend\Decimal getDecimalAttribute(string $modelType, string $attributeCode)
 * @method static \Magento\Eav\Model\Attribute\Backend\Integer getIntegerAttribute(string $modelType, string $attributeCode)
 * @method static \Magento\Eav\Model\Attribute\Backend\Text getTextAttribute(string $modelType, string $attributeCode)
 * @method static \Magento\Eav\Model\Attribute\Backend\Varchar getVarcharAttribute(string $modelType, string $attributeCode)
 * @method static \Magento\Eav\Model\Attribute\Backend\Date getDateAttribute(string $modelType, string $attributeCode)
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
