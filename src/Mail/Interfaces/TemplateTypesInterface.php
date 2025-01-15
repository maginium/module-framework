<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Interfaces;

use Magento\Framework\App\TemplateTypesInterface as BaseTemplateTypesInterface;

/**
 * Template Types interface.
 */
interface TemplateTypesInterface extends BaseTemplateTypesInterface
{
    /**
     * Constant representing the 'React' template type.
     * Used for email templates rendered with React components.
     */
    public const TYPE_REACT = 3;
}
