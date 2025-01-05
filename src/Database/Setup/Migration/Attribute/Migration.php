<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Setup\Migration\Attribute;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Database\Interfaces\RevertablePatchInterface;
use Maginium\Framework\Database\Model;
use Maginium\Framework\Database\Setup\BaseMigration;
use Maginium\Framework\Support\Debug\ConsoleOutput;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Reflection;

/**
 * Attribute Migration Class.
 *
 * This abstract class serves as the base for database patchers that modify the schema
 * during module setup. It is responsible for managing database attribute modifications,
 * supporting both patch application and rollback. It includes methods for database interaction
 * and managing migration context, attributes, and schema updates.
 *
 * @template TModel of Model
 *
 * @method void down() Revert the changes made by the `down` method.
 *
 * @mixin RevertablePatchInterface
 */
abstract class Migration extends BaseMigration implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * The code identifier for the attribute.
     *
     * @var string
     */
    public static string $attribute = '';

    /**
     * The area code indicates the context (e.g., admin, frontend) in which the patch will be applied.
     * If not set, the default area code will be used.
     *
     * @var string|null The area code for the patch.
     */
    protected const AREA_CODE = null;

    /**
     * The context object for managing migration execution.
     *
     * This context contains necessary services, like the module data setup and patch history,
     * required for patch application or rollback.
     *
     * @var Context
     */
    protected $context;

    /**
     * AttributeMigration constructor.
     *
     * Initializes the migration with the necessary context and services for patch execution.
     * The constructor injects dependencies such as context, product attribute, category attribute,
     * and customer attribute services to handle database interactions.
     *
     * @param Context $context Context object providing services like module data setup,
     *                                    patch history, and other necessary services for migration.
     */
    public function __construct(
        Context $context,
    ) {
        // Initialize the migration with the provided context and services
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     *
     * Applies the patch. This method is called to execute the patch's changes to the database.
     * It checks if the patch has already been applied (to avoid reapplying), sets the area code if needed,
     * and invokes the `execute()` method to apply the migration logic.
     */
    final public function apply(): void
    {
        // Check if the patch was already applied under a different alias
        if ($appliedAlias = $this->hasAlreadyAppliedAlias()) {
            // Log that the patch was skipped because it was already applied under the old alias
            Log::info(
                __('Patch "%1" skipped. Already applied with alias "%2".', [static::class, $appliedAlias])->render(),
            );

            // Skip execution if already applied
            return;
        }

        // Start setup process (begin database transaction)
        $this->getConnection()->startSetup();

        // Set area code if defined in the class constant
        if ($areaCode = static::AREA_CODE) {
            try {
                // Attempt to set the area code for the operation (e.g., admin, frontend)
                $this->context->getState()->setAreaCode($areaCode);
            } catch (Exception $th) {
                // If area code is already set, no need to set it again
                // Log the error if needed (can be added for debugging purposes)
            }
        }

        // Log the start of the migration
        ConsoleOutput::info('🔨 Starting migration for attribute: "' . static::$attribute . '"...', false);

        // Call the method to define the schema for the attribute based on the attribute
        $this->up();

        // End setup process (commit database transaction)
        $this->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     *
     * Reverts the patch. This method is called to undo the changes made by the patch.
     * It invokes the `rollback()` method to allow for custom undo logic.
     */
    final public function revert(): void
    {
        // Start setup process (begin database transaction)
        $this->getConnection()->startSetup();

        // Revert the patch's changes (if any custom rollback logic is implemented)
        if (Reflection::methodExists($this, 'down')) {
            // Log the start of the rollback
            ConsoleOutput::info('🗑️ Starting rollback for attribute: "' . static::$attribute . '"...', false);

            $this->down();

            // Log the completion of the rollback
            ConsoleOutput::success('Rollback completed: "' . static::$attribute . '" attribute dropped successfully!');
        }

        // End setup process (commit database transaction)
        $this->getConnection()->endSetup();
    }

    /**
     * Creates the database schema for the respective attribute attribute.
     *
     * This method must be implemented by the subclass to define the database columns,
     * their types, and properties using the provided AttributeBlueprint instance for the specified
     * attribute model. The implementation will vary based on the type of attribute (product, category, or customer).
     *
     * @return void
     */
    abstract public function up(): void;
}
