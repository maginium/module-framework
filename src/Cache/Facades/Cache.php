<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Facades;

use Maginium\Framework\Cache\Interfaces\FactoryInterface;
use Maginium\Framework\Support\Facade;

/**
 * @method static \Illuminate\Contracts\Cache\Repository store(string|null $name = null)
 * @method static \Illuminate\Contracts\Cache\Repository driver(string|null $driver = null)
 * @method static \Illuminate\Contracts\Cache\Repository resolve(string $name)
 * @method static \Maginium\Framework\Cache\Repository build(array $config)
 * @method static \Maginium\Framework\Cache\Repository repository(\Illuminate\Contracts\Cache\Store $store, array $config = [])
 * @method static void refreshEventDispatcher()
 * @method static string getDefaultDriver()
 * @method static void setDefaultDriver(string $name)
 * @method static \Maginium\Framework\Cache\CacheManager forgetDriver(array|string|null $name = null)
 * @method static void purge(string|null $name = null)
 * @method static \Maginium\Framework\Cache\CacheManager extend(string $driver, \Closure $callback)
 * @method static \Maginium\Framework\Cache\CacheManager setApplication(\Illuminate\Contracts\Foundation\Application $app)
 * @method static bool has(array|string $key)
 * @method static bool missing(string $key)
 * @method static mixed get(array|string $key, mixed $default = null)
 * @method static array many(array $keys)
 * @method static iterable getMultiple(iterable $keys, mixed $default = null)
 * @method static mixed pull(array|string $key, mixed $default = null)
 * @method static bool put(array|string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool set(string $key, mixed $value, null|int|\DateInterval $ttl = null)
 * @method static bool putMany(array $values, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static bool setMultiple(iterable $values, null|int|\DateInterval $ttl = null)
 * @method static bool add(string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null)
 * @method static int|bool increment(string $key, mixed $value = 1)
 * @method static int|bool decrement(string $key, mixed $value = 1)
 * @method static bool forever(string $key, mixed $value)
 * @method static mixed remember(string $key, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl, \Closure $callback)
 * @method static mixed sear(string $key, \Closure $callback)
 * @method static mixed rememberForever(string $key, \Closure $callback)
 * @method static mixed flexible(string $key, array $ttl, callable $callback, array|null $lock = null)
 * @method static bool forget(string $key)
 * @method static bool delete(string $key)
 * @method static bool deleteMultiple(iterable $keys)
 * @method static bool clear()
 * @method static \Maginium\Framework\Cache\TaggedCache tags(array $names)
 * @method static bool supportsTags()
 * @method static int|null getDefaultCacheTime()
 * @method static \Maginium\Framework\Cache\Repository setDefaultCacheTime(int|null $seconds)
 * @method static \Illuminate\Contracts\Cache\Store getStore()
 * @method static \Maginium\Framework\Cache\Repository setStore(\Illuminate\Contracts\Cache\Store $store)
 * @method static \Illuminate\Contracts\Events\Dispatcher|null getEventDispatcher()
 * @method static void setEventDispatcher(\Illuminate\Contracts\Events\Dispatcher $events)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 * @method static bool flush()
 * @method static string getPrefix()
 * @method static \Illuminate\Contracts\Cache\Lock lock(string $name, int $seconds = 0, string|null $owner = null)
 * @method static \Illuminate\Contracts\Cache\Lock restoreLock(string $name, string $owner)
 *
 * @see FactoryInterface
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
        return FactoryInterface::class;
    }
}
