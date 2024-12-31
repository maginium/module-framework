<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Facades;

use Maginium\Framework\Cache\Interfaces\CacheInterface;
use Maginium\Framework\Support\Facade;

/**
 * @method static void refreshEventDispatcher()
 * @method static void purge(string|null $name = null)
 * @method static bool has($key)
 * @method static bool missing(string $key)
 * @method static mixed get($key, mixed $default = null)
 * @method static array many(array $keys)
 * @method static iterable getMultiple(iterable $keys, mixed $default = null)
 * @method static mixed pull($key, mixed $default = null)
 * @method static bool put($key, mixed $value, array $tags = [], \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool set($key, mixed $value, array $tags = [], \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool putMany(array $values, array $tags = [], \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool setMultiple(mixed $values, array $tags = [], \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool add(string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static int|bool increment(string $key, mixed $value = 1)
 * @method static int|bool decrement(string $key, mixed $value = 1)
 * @method static bool forever(string $key, mixed $value, array $tags = [])
 * @method static TCacheValue remember(string $key, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl, \Closure(): TCacheValue $callback)
 * @method static TCacheValue sear(string $key, \Closure(): TCacheValue $callback)
 * @method static TCacheValue rememberForever(string $key, \Closure(): TCacheValue $callback)
 * @method static TCacheValue flexible(string $key, array{0: \DateTimeInterface|\DateInterval|int, 1: \DateTimeInterface|\DateInterval|int} $ttl, callable(): TCacheValue $callback, array{seconds?: int, owner?: string}|null $lock = null)
 * @method static bool forget(string $key)
 * @method static bool delete(string $key)
 * @method static bool deleteMultiple(array $keys)
 * @method static bool clear()
 *
 * @see CacheInterface
 */
class Cache extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    protected static function getAccessor(): string
    {
        return CacheInterface::class;
    }
}
