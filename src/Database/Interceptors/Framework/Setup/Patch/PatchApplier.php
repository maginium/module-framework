<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interceptors\Framework\Setup\Patch;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Setup\Exception as SetupException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\PatchApplier as BasePatchApplier;
use Magento\Framework\Setup\Patch\PatchBackwardCompatability;
use Magento\Framework\Setup\Patch\PatchFactory;
use Magento\Framework\Setup\Patch\PatchHistory;
use Magento\Framework\Setup\Patch\PatchReader;
use Magento\Framework\Setup\Patch\PatchRegistry;
use Magento\Framework\Setup\Patch\PatchRegistryFactory;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\SetupInterface;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Support\Validator;

/**
 * Apply patches per specific module.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PatchApplier extends BasePatchApplier
{
    /**
     * @var PatchBackwardCompatability
     */
    private $patchBackwardCompatability;

    /**
     * @var PatchHistory
     */
    private $patchHistory;

    /**
     * @var PatchRegistryFactory
     */
    private $patchRegistryFactory;

    /**
     * @var ModuleList
     */
    private $moduleList;

    /**
     * @var PatchFactory
     */
    private $patchFactory;

    /**
     * @var SetupInterface
     */
    private $schemaSetup;

    /**
     * @var PatchReader
     */
    private $dataPatchReader;

    /**
     * @var PatchReader
     */
    private $schemaPatchReader;

    /**
     * PatchApplier constructor.
     *
     * @param ModuleList $moduleList
     * @param PatchHistory $patchHistory
     * @param PatchFactory $patchFactory
     * @param PatchReader $dataPatchReader
     * @param PatchReader $schemaPatchReader
     * @param SchemaSetupInterface $schemaSetup
     * @param ObjectManagerInterface $objectManager
     * @param ResourceConnection $resourceConnection
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param PatchRegistryFactory $patchRegistryFactory
     * @param PatchBackwardCompatability $patchBackwardCompatability
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(Magento.TypeDuplication)
     */
    public function __construct(
        PatchHistory $patchHistory,
        PatchFactory $patchFactory,
        PatchReader $dataPatchReader,
        PatchReader $schemaPatchReader,
        SchemaSetupInterface $schemaSetup,
        ObjectManagerInterface $objectManager,
        ResourceConnection $resourceConnection,
        ModuleDataSetupInterface $moduleDataSetup,
        PatchRegistryFactory $patchRegistryFactory,
        PatchBackwardCompatability $patchBackwardCompatability,
        ModuleList $moduleList,
    ) {
        // Call the parent constructor to pass the arguments to the base class
        parent::__construct(
            $dataPatchReader,
            $schemaPatchReader,
            $patchRegistryFactory,
            $resourceConnection,
            $patchBackwardCompatability,
            $patchHistory,
            $patchFactory,
            $objectManager,
            $schemaSetup,
            $moduleDataSetup,
            $moduleList,
        );

        // Assign dependencies to the class properties
        $this->moduleList = $moduleList;
        $this->schemaSetup = $schemaSetup;
        $this->patchHistory = $patchHistory;
        $this->patchFactory = $patchFactory;
        $this->dataPatchReader = $dataPatchReader;
        $this->schemaPatchReader = $schemaPatchReader;
        $this->patchRegistryFactory = $patchRegistryFactory;
        $this->patchBackwardCompatability = $patchBackwardCompatability;
    }

    /**
     * Apply all patches for one module.
     *
     * Please note: that schema patches are not revertable
     *
     * @param null|string $moduleName
     *
     * @throws SetupException
     */
    public function applySchemaPatch($moduleName = null)
    {
        // Prepare the registry for patches by reading schema patches
        $registry = $this->prepareRegistry($moduleName, self::SCHEMA_PATCH);

        // Loop through each patch in the registry
        foreach ($registry as $schemaPatch) {
            try {
                // Skip patches that were applied in old style (based on version check)
                if ($this->patchBackwardCompatability->isSkipableBySchemaSetupVersion($schemaPatch, $moduleName)) {
                    // Mark patch as fixed
                    $this->patchHistory->fixPatch($schemaPatch);

                    // Skip to the next patch
                    continue;
                }

                /**
                 * @var SchemaPatchInterface $schemaPatch
                 */
                // Create a schema patch instance using the patch factory
                $schemaPatch = $this->patchFactory->create($schemaPatch, ['schemaSetup' => $this->schemaSetup]);

                // Apply the patch to the schema
                $schemaPatch->apply();

                // Log the successful application of the patch
                $this->patchHistory->fixPatch(get_class($schemaPatch));

                // Apply any aliases for this patch (i.e., other related patches)
                foreach ($schemaPatch->getAliases() as $patchAlias) {
                    if (! $this->patchHistory->isApplied($patchAlias)) {
                        $this->patchHistory->fixPatch($patchAlias);
                    }
                }
            } catch (Exception $e) {
                // If applying the patch fails, revert it
                $schemaPatch->revert();

                // Get the class name of the schema patch (in case it's an object)
                $schemaPatchClass = Validator::isObject($schemaPatch) ? get_class($schemaPatch) : $schemaPatch;

                // Throw a setup exception with detailed error message
                throw new SetupException(
                    new Phrase(
                        'Unable to apply patch %1 for module %2. Original exception message: %3',
                        [
                            $schemaPatchClass, // Patch class name
                            $moduleName, // Module name
                            $e->getMessage(), // Original exception message
                        ],
                    ),
                );
            } finally {
                // Unset the schema patch object to free up memory
                unset($schemaPatch);
            }
        }
    }

    /**
     * Register all patches in registry in order to manipulate chains and dependencies of patches.
     *
     * @param string $moduleName
     * @param string $patchType
     *
     * @return PatchRegistry
     */
    private function prepareRegistry($moduleName, $patchType): PatchRegistry
    {
        // Select the appropriate patch reader based on the patch type (data or schema)
        $reader = $patchType === self::DATA_PATCH ? $this->dataPatchReader : $this->schemaPatchReader;

        // Create a new patch registry instance
        $registry = $this->patchRegistryFactory->create();

        // Prepare the list of patches to read based on module name
        if ($moduleName === null) {
            $patchNames = [];

            // Loop through all enabled modules and read their patches
            foreach ($this->moduleList->getNames() as $moduleName) {
                // Collect patch names
                $patchNames += $reader->read($moduleName);
            }
        } else {
            // Read patches for the specific module
            $patchNames = $reader->read($moduleName);
        }

        // Register each patch in the registry
        foreach ($patchNames as $patchName) {
            $registry->registerPatch($patchName);
        }

        // Return the prepared registry
        return $registry;
    }
}
