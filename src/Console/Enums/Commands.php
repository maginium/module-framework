<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Enums;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Enum representing different console commands.
 *
 * @method static self ABOUT() Represents the command to show application information.
 * @method static self CACHE_FLUSH() Represents the command to flush the cache.
 * @method static self MODULE_LIST() Represents the command to list all available modules.
 * @method static self MODULE_ENABLE() Represents the command to enable a module.
 * @method static self MODULE_DISABLE() Represents the command to disable a module.
 * @method static self SETUP_UPGRADE() Represents the command to upgrade the setup.
 * @method static self SETUP_DI() Represents the command to generate dependency injection configuration.
 * @method static self REINDEX() Represents the command to reindex data.
 * @method static self STATIC_CONTENT_DEPLOY() Represents the command to deploy static content.
 * @method static self INDEXER_REBUILD() Represents the command to rebuild the indexer.
 * @method static self CACHE_CLEAN() Represents the command to clean the cache.
 * @method static self DB_SEED() Represents the command to seed the database.
 * @method static self DB_MIGRATE() Represents the command to migrate the database.
 * @method static self LIST_COMMANDS() Represents the command to list all available commands.
 */
class Commands extends Enum
{
    /**
     * Represents the command to show application information.
     */
    #[Label('About')]
    #[Description('Represents the command to show application information.')]
    public const ABOUT = 'app:about';

    /**
     * Represents the command to flush the cache.
     */
    #[Label('Cache Flush')]
    #[Description('Represents the command to flush the cache.')]
    public const CACHE_FLUSH = 'app:cache:flush';

    /**
     * Represents the command to list all available modules.
     */
    #[Label('Module List')]
    #[Description('Represents the command to list all available modules.')]
    public const MODULE_LIST = 'app:module:list';

    /**
     * Represents the command to enable a module.
     */
    #[Label('Module Enable')]
    #[Description('Represents the command to enable a module.')]
    public const MODULE_ENABLE = 'app:module:enable';

    /**
     * Represents the command to disable a module.
     */
    #[Label('Module Disable')]
    #[Description('Represents the command to disable a module.')]
    public const MODULE_DISABLE = 'app:module:disable';

    /**
     * Represents the command to upgrade the setup.
     */
    #[Label('Setup Upgrade')]
    #[Description('Represents the command to upgrade the setup.')]
    public const SETUP_UPGRADE = 'app:setup:upgrade';

    /**
     * Represents the command to generate dependency injection configuration.
     */
    #[Label('Setup DI Compile')]
    #[Description('Represents the command to generate dependency injection configuration.')]
    public const SETUP_DI = 'app:setup:di:compile';

    /**
     * Represents the command to reindex data.
     */
    #[Label('Reindex')]
    #[Description('Represents the command to reindex data.')]
    public const REINDEX = 'app:indexer:reindex';

    /**
     * Represents the command to deploy static content.
     */
    #[Label('Static Content Deploy')]
    #[Description('Represents the command to deploy static content.')]
    public const STATIC_CONTENT_DEPLOY = 'app:setup:static-content:deploy';

    /**
     * Represents the command to rebuild the indexer.
     */
    #[Label('Indexer Rebuild')]
    #[Description('Represents the command to rebuild the indexer.')]
    public const INDEXER_REBUILD = 'app:indexer:rebuild';

    /**
     * Represents the command to clean the cache.
     */
    #[Label('Cache Clean')]
    #[Description('Represents the command to clean the cache.')]
    public const CACHE_CLEAN = 'app:cache:clean';

    /**
     * Represents the command to seed the database.
     */
    #[Label('Database Seed')]
    #[Description('Represents the command to seed the database.')]
    public const DB_SEED = 'app:db:seed';

    /**
     * Represents the command to migrate the database.
     */
    #[Label('Database Migrate')]
    #[Description('Represents the command to migrate the database.')]
    public const DB_MIGRATE = 'app:db:migrate';

    /**
     * Represents the command to list all available commands.
     */
    #[Label('List Commands')]
    #[Description('Represents the command to list all available commands.')]
    public const LIST_COMMANDS = 'app:list:commands';
}
