<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Setup\Seeder;

use Magento\Framework\App\State;
use Magento\Framework\Setup\ModuleDataSetupInterface as ModuleDataSetup;
use Magento\Framework\Setup\Patch\PatchHistory;

/**
 * Class Context.
 *
 * This class serves as a container for the necessary services and state required for database patching
 * operations within the context of Magento's setup process. It provides access to the module data setup,
 * patch history, and application state to manage database patch operations effectively.
 */
class Context
{
    /**
     * This is an instance of the `ModuleDataSetupInterface` used to interact with the database during setup.
     * It provides methods to execute SQL queries, modify schema, and manage the setup process.
     *
     * @var ModuleDataSetup
     */
    private ModuleDataSetup $moduleDataSetup;

    /**
     * This property holds the instance of `PatchHistory`, which tracks the history of applied patches.
     * It helps to determine which patches have been applied and prevent duplicate patch execution.
     *
     * @var PatchHistory
     */
    private PatchHistory $patchHistory;

    /**
     * The `State` object holds the current state of the Magento application. It's used to manage states
     * such as store configuration, running mode, and more, providing necessary context for patch operations.
     *
     * @var State
     */
    private State $state;

    /**
     * Constructor method for the Context class.
     *
     * Initializes the context with the required dependencies: ModuleDataSetup, PatchHistory, and State.
     *
     * @param State $state The state of the Magento application, used for retrieving configuration and store details.
     * @param PatchHistory $patchHistory The history of applied patches to track setup progress.
     * @param ModuleDataSetup $moduleDataSetup The setup interface used to interact with the database.
     */
    // phpcs:ignore
    public function __construct(
        State $state,
        PatchHistory $patchHistory,
        ModuleDataSetup $moduleDataSetup,
    ) {
        $this->state = $state;
        $this->patchHistory = $patchHistory;
        $this->moduleDataSetup = $moduleDataSetup;
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
}
