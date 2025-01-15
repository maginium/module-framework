<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Models\Template\Config;

use Magento\Email\Model\Template\Config\SchemaLocator as BaseSchemaLocator;
use Magento\Framework\Config\SchemaLocatorInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Module\Dir\Reader as ModuleReader;
use Maginium\Framework\Support\Path;

/**
 * Class SchemaLocator
 * This class is responsible for locating the custom email template XSD schema for the module.
 * It extends the base SchemaLocator and implements the SchemaLocatorInterface.
 */
class SchemaLocator extends BaseSchemaLocator implements SchemaLocatorInterface
{
    /**
     * @var string|null
     * Stores the path to the custom email template XSD schema.
     */
    protected $schemaPath = null;

    /**
     * SchemaLocator constructor.
     *
     * This constructor initializes the schema path by utilizing the module directory reader
     * to retrieve the path to the custom email template XSD schema.
     *
     * @param ModuleReader $moduleReader
     *
     * @see Reader
     */
    public function __construct(ModuleReader $moduleReader)
    {
        // Get the directory path to the module's etc folder
        $moduleDir = $moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Maginium_Framework');

        // Construct the full path to the XSD schema file for email templates
        $this->schemaPath = Path::join($moduleDir, 'xsds', 'email_templates.xsd');
    }

    /**
     * Get the schema path for email templates.
     *
     * This method is required by the SchemaLocatorInterface and returns the path to the custom email template XSD.
     *
     * @return string The path to the XSD schema for email templates.
     */
    public function getSchema(): string
    {
        return $this->schemaPath;
    }

    /**
     * Get the per-file schema path.
     *
     * This method is required by the SchemaLocatorInterface and returns the schema path for individual email templates.
     *
     * @return string The path to the XSD schema for per-file email templates.
     */
    public function getPerFileSchema(): string
    {
        return $this->schemaPath;
    }
}
