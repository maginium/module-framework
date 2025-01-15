<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Models;

use Maginium\Framework\Support\Collection;

/**
 * Registry for managing custom email templates in Magento's email configuration.
 *
 * This class extends the `Collection` to handle a list of custom email templates.
 * It provides functionality for retrieving and managing the email templates within the
 * Magento email configuration system.
 */
class TemplateRegistry extends Collection
{
    /**
     * @var array List of custom email templates to be registered.
     */
    protected array $templates;

    /**
     * TemplateRegistry constructor.
     *
     * Initializes the registry with a list of custom email templates.
     *
     * @param array $templates List of custom email templates to be added to the registry.
     */
    public function __construct(array $templates = [])
    {
        $this->templates = $templates;

        // Call the parent constructor to initialize the datasource registry with custom templates
        parent::__construct($templates);
    }

    /**
     * Retrieve all registered email templates.
     *
     * This method returns the list of custom email templates registered in the system.
     * The templates are fetched from the datasources inherited from the parent class.
     *
     * @return array An array of all registered email templates.
     */
    public function getTemplates(): array
    {
        // Fetch and return the list of all email templates from the datasources
        return $this->all();
    }
}
