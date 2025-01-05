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
}
