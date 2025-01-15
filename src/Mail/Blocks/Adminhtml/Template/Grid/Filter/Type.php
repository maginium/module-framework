<?php

declare(strict_types=1);

namespace Magento\Email\Block\Adminhtml\Template\Grid\Filter\Type;

use Magento\Email\Block\Adminhtml\Template\Grid\Filter\Type as BaseType;
use Maginium\Framework\Mail\Interfaces\TemplateTypesInterface;

/**
 * Adminhtml system templates grid block type item renderer.
 *
 * This class renders the type of email templates (HTML, Text, React) in the admin panel grid.
 * It extends from Magento's base renderer to customize the template type display.
 */
class Type extends BaseType
{
    /**
     * Email template types.
     *
     * This static array maps the template type constants to their corresponding string representation.
     * The constants are defined in the TemplateTypesInterface.
     *
     * @var array
     */
    protected static $_types = [
        TemplateTypesInterface::TYPE_HTML => 'HTML', // Represents HTML email templates.
        TemplateTypesInterface::TYPE_TEXT => 'Text', // Represents Text email templates.
        TemplateTypesInterface::TYPE_REACT => 'React', // Represents React-based email templates.
    ];
}
