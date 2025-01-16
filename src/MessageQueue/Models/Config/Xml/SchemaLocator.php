<?php

declare(strict_types=1);

namespace Maginium\Framework\MessageQueue\Models\Config\Xml;

use Magento\Framework\Config\Dom\UrnResolver;
use Magento\Framework\Config\SchemaLocatorInterface;
use Magento\Framework\MessageQueue\Topology\Config\Xml\SchemaLocator as BaseSchemaLocator;
use Magento\Framework\Module\Dir;
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
     * SchemaLocator constructor.
     *
     * Initializes the paths to the module-specific and framework XSD schema files
     * required for email templates and message queue topology validation.
     *
     * @param ModuleReader $moduleReader Provides access to the module's directory structure.
     * @param UrnResolver $urnResolver Resolves URNs to their real file paths.
     */
    public function __construct(ModuleReader $moduleReader, UrnResolver $urnResolver)
    {
        // Resolve the directory for the module's etc folder
        $moduleEtcDir = $moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Maginium_Framework');

        // Set the schema path for the module-specific XSD file
        $this->schema = Path::join($moduleEtcDir, 'xsds', 'topology.xsd');

        // Resolve the real path for the framework's topology XSD file
        $this->perFileSchema = Path::join($moduleEtcDir, 'xsds', 'topology_merged.xsd');
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
        return $this->schema;
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
        return $this->perFileSchema;
    }
}
