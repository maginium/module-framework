<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Interceptors\Model;

use Magento\Email\Model\AbstractTemplate as BaseAbstractTemplate;
use Magento\Email\Model\Template\Config;
use Maginium\Framework\Mail\Interfaces\TemplateTypesInterface;

/**
 * Template model class to modify the template type selection logic.
 *
 * This class intercepts the loadDefault method of the EmailMessage and modifies
 * the template type based on configuration and conditions, including a specific
 * handling for 'react' templates.
 */
class AbstractTemplate
{
    /**
     * @var Config
     *
     * The email configuration object used to retrieve template type information.
     */
    protected $emailConfig;

    /**
     * Constructor to inject the email configuration dependency.
     *
     * @param Config $emailConfig The email configuration to retrieve template type.
     */
    public function __construct(Config $emailConfig)
    {
        $this->emailConfig = $emailConfig;
    }

    /**
     * After plugin method to set the template type after loading the default template.
     *
     * This method is executed after the `loadDefault` method of `BaseAbstractTemplate`.
     * It retrieves the template type from the configuration and modifies the template
     * type based on custom logic (e.g., 'react' type templates).
     *
     * @param BaseAbstractTemplate $subject The instance of the subject class.
     * @param BaseAbstractTemplate $result The result of the method being intercepted.
     * @param string $templateId The template ID to fetch the type for.
     *
     * @return BaseAbstractTemplate The modified result object with the updated template type.
     */
    public function afterLoadDefault(
        BaseAbstractTemplate $subject,
        BaseAbstractTemplate $result,
        string $templateId,
    ): BaseAbstractTemplate {
        // Retrieve the template type from the email config using the template ID
        $templateType = $this->emailConfig->getTemplateType($templateId);

        // Set template type code based on configuration and custom conditions
        $templateTypeCode = $this->determineTemplateTypeCode($templateType);

        // Set the modified template type in the subject
        $subject->setTemplateType($templateTypeCode);

        // Return the result, which is typically the modified subject
        return $result;
    }

    /**
     * Determine the template type code based on the template type string.
     *
     * This helper method adds logic to support custom template types like 'react'.
     *
     * @param string $templateType The template type string from the configuration.
     *
     * @return int The corresponding template type code.
     */
    private function determineTemplateTypeCode(string $templateType): int
    {
        if ($templateType === 'react') {
            // Handle 'react' type templates
            return TemplateTypesInterface::TYPE_REACT;
        }

        // Default handling for 'html' and 'text' types
        return $templateType === 'html'
            ? TemplateTypesInterface::TYPE_HTML
            : TemplateTypesInterface::TYPE_TEXT;
    }
}
