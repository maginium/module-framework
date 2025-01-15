<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Interceptors\Framework\Mail\Model\Config\Source;

use Magento\Config\Model\Config\Source\Email\Template as BaseTemplate;
use Magento\Email\Model\Template\Config;
use Maginium\Framework\Mail\Models\TemplateRegistry;
use Maginium\Framework\Support\Arr;

/**
 * Plugin to extend the available email templates in Magento's email configuration.
 *
 * This plugin appends custom email templates to the list of available email templates
 * in Magento's configuration options by modifying the result of the `toOptionArray` method.
 */
class Template
{
    /**
     * @var Config The email template configuration service
     */
    protected Config $emailConfig;

    /**
     * @var TemplateRegistry Registry of custom email templates.
     */
    protected TemplateRegistry $templateRegistry;

    /**
     * Template constructor.
     *
     * @param Config $emailConfig The email template configuration service.
     * @param TemplateRegistry $emailRegistry Registry of custom email templates.
     */
    public function __construct(Config $emailConfig, TemplateRegistry $templateRegistry)
    {
        $this->emailConfig = $emailConfig;
        $this->templateRegistry = $templateRegistry;
    }

    /**
     * After plugin method to modify the email templates options array.
     *
     * This method is invoked after the `toOptionArray` method of the subject (email template source)
     * to append custom email templates to the existing list of email templates.
     *
     * @param Template $subject The subject instance of the email template source.
     * @param array $result The existing array of email template options.
     *
     * @return array The updated options array, including the custom email templates.
     */
    public function afterToOptionArray(BaseTemplate $subject, array $result): array
    {
        // Getting all templates
        $templates = $this->templateRegistry->getTemplates();

        // Merge existing email templates options with custom templates
        return Arr::merge($result, Arr::each([$this, 'getOptions'], $templates));
    }

    /**
     * Generate the option array for a specific custom email template ID.
     *
     * This method retrieves the label for the given email template ID and constructs
     * an array containing the template's label and ID to be included in the email templates list.
     *
     * @param string $id The unique identifier of the custom email template.
     *
     * @return array The option array containing 'label' and 'value' for the email template.
     */
    public function getOptions(string $id): array
    {
        // Fetch the email template label using the email configuration service
        $emailTemplateLabel = $this->emailConfig->getTemplateLabel($id);

        // Return the label and value as an array, where label is translated
        return [
            'label' => __($emailTemplateLabel), // Translating the label
            'value' => $id, // Using the template ID as the value
        ];
    }
}
