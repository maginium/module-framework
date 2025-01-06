<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Facades;

use Illuminate\Support\Traits\Macroable;
use Magento\Customer\Api\CustomerMetadataInterface;
use Maginium\Framework\Database\Services\EavAttribute;

/**
 * CustomerAttribute class for managing customer-related EAV attributes.
 *
 * This class extends the EavAttribute class to handle customer-specific EAV attributes.
 * It defines the model type for the customer attributes, allowing interaction with
 * customer data in Magento's EAV (Entity-Attribute-Value) system.
 */
class CustomerAttribute extends EavAttribute
{
    use Macroable;

    /**
     * Entity type constant for customer attributes.
     *
     * This constant defines the model type for customer attributes, which is used in the
     * EAV system to associate attributes with the customer model.
     *
     * @var string
     */
    public const ENTITY_TYPE = CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER;

    /**
     * Table constant for website-dependent attribute parameters.
     *
     * This constant represents the table where website-specific attribute settings are stored.
     *
     * @var string
     */
    public const TABLE_EAV_WEBSITE = 'customer_eav_attribute_website';

    /**
     * Table constant for form-attribute dependencies.
     *
     * This constant represents the table where the relationship between forms and attribute IDs is stored.
     *
     * @var string
     */
    public const TABLE_FORM_ATTRIBUTE = 'customer_form_attribute';

    /**
     * Get EAV website table.
     *
     * Get the table where website-dependent attribute parameters are stored.
     * If this functionality is not required, this method returns null.
     *
     * @return string|null
     */
    public function _getEavWebsiteTable(): ?string
    {
        return $this->getConnection()->getTableName(self::TABLE_EAV_WEBSITE);
    }

    /**
     * Get Form attribute table.
     *
     * Get the table where the relationship between form names and attribute IDs is stored.
     *
     * @return string|null
     */
    public function _getFormAttributeTable(): ?string
    {
        return $this->getConnection()->getTableName(self::TABLE_FORM_ATTRIBUTE);
    }
}
