<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Enums;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Enum representing different cache-related events in the caching lifecycle.
 *
 * This class standardizes the event types used throughout the caching system
 * to ensure consistency and type safety when triggering or handling cache events.
 * Each event is annotated with labels and descriptions for better clarity and maintainability.
 *
 * @method static self HIT() Represents the cache hit event, triggered when a cache key is successfully retrieved.
 * @method static self MISSED() Represents the cache missed event, triggered when a cache key is not found.
 * @method static self RETRIEVING() Represents the cache retrieving event, triggered when attempting to fetch a single cache key.
 * @method static self RETRIEVING_MANY() Represents the cache retrieving many keys event, triggered when attempting to fetch multiple keys.
 * @method static self WRITING() Represents the cache writing event, triggered when storing a value in the cache.
 * @method static self WRITTEN() Represents the cache written event, triggered when a value is successfully written to the cache.
 * @method static self WRITE_FAILED() Represents the cache write failed event, triggered when a value fails to be written to the cache.
 * @method static self FORGETTING() Represents the cache forgetting event, triggered when a cache key is being deleted.
 * @method static self FORGOTTEN() Represents the cache forgotten event, triggered when a cache key is successfully deleted.
 * @method static self FORGET_FAILED() Represents the cache forget failed event, triggered when a cache key fails to be deleted.
 * @method static self WRITING_MANY() Represents the cache writing many keys event, triggered when storing multiple values in the cache.
 */
class CacheEvents extends Enum
{
    /**
     * Represents the event triggered when a cache key is successfully retrieved.
     */
    #[Label('Hit')]
    #[Description('Represents the cache hit event.')]
    public const HIT = 'hit';

    /**
     * Represents the event triggered when a cache key is not found.
     */
    #[Label('Missed')]
    #[Description('Represents the cache missed event.')]
    public const MISSED = 'missed';

    /**
     * Represents the event triggered when attempting to fetch a single cache key.
     */
    #[Label('Retrieving')]
    #[Description('Represents the cache retrieving event.')]
    public const RETRIEVING = 'retrieving';

    /**
     * Represents the event triggered when attempting to fetch multiple cache keys at once.
     */
    #[Label('Retrieving Many')]
    #[Description('Represents the cache retrieving many keys event.')]
    public const RETRIEVING_MANY = 'retrieving_many';

    /**
     * Represents the event triggered when storing a value in the cache.
     */
    #[Label('Writing')]
    #[Description('Represents the cache writing event.')]
    public const WRITING = 'writing';

    /**
     * Represents the event triggered when a value is successfully written to the cache.
     */
    #[Label('Written')]
    #[Description('Represents the cache written event.')]
    public const WRITTEN = 'written';

    /**
     * Represents the event triggered when writing a value to the cache fails.
     */
    #[Label('Write Failed')]
    #[Description('Represents the cache write failed event.')]
    public const WRITE_FAILED = 'write_failed';

    /**
     * Represents the event triggered when a cache key is being deleted.
     */
    #[Label('Forgetting')]
    #[Description('Represents the cache forgetting event.')]
    public const FORGETTING = 'forgetting';

    /**
     * Represents the event triggered when a cache key is successfully deleted.
     */
    #[Label('Forgotten')]
    #[Description('Represents the cache forgotten event.')]
    public const FORGOTTEN = 'forgotten';

    /**
     * Represents the event triggered when deleting a cache key fails.
     */
    #[Label('Forget Failed')]
    #[Description('Represents the cache forget failed event.')]
    public const FORGET_FAILED = 'forget_failed';

    /**
     * Represents the event triggered when storing multiple values in the cache.
     */
    #[Label('Writing Many')]
    #[Description('Represents the cache writing many keys event.')]
    public const WRITING_MANY = 'writing_many';
}
