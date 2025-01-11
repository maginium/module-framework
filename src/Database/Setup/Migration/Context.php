<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Setup\Migration;

use Magento\Framework\App\DeploymentConfig\Writer as DeploymentConfig;
use Magento\Framework\App\State;
use Magento\Framework\Setup\ModuleDataSetupInterface as ModuleDataSetup;
use Magento\Framework\Setup\Patch\PatchHistory;
use Magento\Framework\Setup\SchemaSetupInterface;
use Maginium\Framework\Database\Facades\AdminConfig;

/**
 * Class Context.
 *
 * This class encapsulates the necessary services and state required for performing database patching
 * operations in Magento's setup process. It provides controlled access to several dependencies, including
 * module data setup, patch history, application state, EAV configuration, and more, all of which are essential
 * for managing and executing database modifications efficiently and securely.
 */
class Context
{
    /**
     *  Interface for interacting with the Magento database during module setup.
     *
     * @var ModuleDataSetup
     */
    private ModuleDataSetup $moduleDataSetup;

    /**
     *  Records and manages the history of applied database patches.
     *
     * @var PatchHistory
     */
    private PatchHistory $patchHistory;

    /**
     *  Represents the current state of the Magento application (e.g., configuration and store context).
     *
     * @var State
     */
    private State $state;

    /**
     *  Retrieves the deployment configuration manager.
     *
     * @var DeploymentConfig
     */
    private DeploymentConfig $deploymentConfig;

    /**
     * Manages and retrieves admin-specific configuration settings.
     *
     * @var AdminConfig
     */
    private AdminConfig $adminConfig;

    /**
     * Provides schema setup interface for database-related operations.
     *
     * @var SchemaSetupInterface
     */
    private SchemaSetupInterface $schemaSetup;

    /**
     * Context constructor.
     *
     * This constructor method injects and initializes all required dependencies for
     * database patching operations, ensuring that essential services are available
     * for interacting with the Magento setup and EAV (Entity-Attribute-Value) models.
     *
     * @param State $state The current application state, used for retrieving configuration and store information.
     * @param DeploymentConfig $deploymentConfig Retrieves the deployment configuration manager.
     * @param AdminConfig $adminConfig Manages admin-specific configuration for Magento.
     * @param PatchHistory $patchHistory Tracks the history of applied patches, preventing duplicate executions.
     * @param ModuleDataSetup $moduleDataSetup Provides methods for interacting with the database setup.
     * @param SchemaSetupInterface $schemaSetup Provides schema setup interface for database-related operations.
     */
    public function __construct(
        State $state,
        AdminConfig $adminConfig,
        PatchHistory $patchHistory,
        ModuleDataSetup $moduleDataSetup,
        SchemaSetupInterface $schemaSetup,
        DeploymentConfig $deploymentConfig,
    ) {
        $this->state = $state;
        $this->adminConfig = $adminConfig;
        $this->schemaSetup = $schemaSetup;
        $this->patchHistory = $patchHistory;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * Retrieve the schema setup instance.
     *
     * Provides access to the SchemaSetupInterface for schema manipulation and query execution.
     *
     * @return SchemaSetupInterface The schema setup instance.
     */
    public function getSchemaSetup(): SchemaSetupInterface
    {
        return $this->schemaSetup;
    }

    /**
     * Get the module data setup instance.
     *
     * Provides access to the ModuleDataSetup instance, enabling SQL query execution
     * and schema manipulation during setup operations.
     *
     * @return ModuleDataSetup The instance of ModuleDataSetupInterface for database interaction.
     */
    public function getModuleDataSetup(): ModuleDataSetup
    {
        return $this->moduleDataSetup;
    }

    /**
     * Get the patch history instance.
     *
     * Provides access to PatchHistory, which tracks applied patches to prevent re-execution
     * of database patches that have already been processed.
     *
     * @return PatchHistory The instance of PatchHistory for tracking applied patches.
     */
    public function getPatchHistory(): PatchHistory
    {
        return $this->patchHistory;
    }

    /**
     * Get the application state.
     *
     * Retrieves the State instance, representing the current application state.
     * Useful for accessing configuration values or detecting the current running mode.
     *
     * @return State The application state instance.
     */
    public function getState(): State
    {
        return $this->state;
    }

    /**
     * Retrieves the deployment configuration manager.
     *
     * Provides access to the deployment configuration instance, which manages
     * system settings and configurations for deployment, such as environment
     * variables and runtime parameters.
     *
     * @return DeploymentConfig The configuration instance for deployment management.
     */
    public function getDeploymentConfig(): DeploymentConfig
    {
        return $this->deploymentConfig;
    }

    /**
     * Get the admin configuration manager.
     *
     * Returns the AdminConfig instance, used for accessing and managing
     * admin-specific configuration settings within Magento.
     *
     * @return AdminConfig The instance of AdminConfig for admin configuration management.
     */
    public function getAdminConfig(): AdminConfig
    {
        return $this->adminConfig;
    }
}
