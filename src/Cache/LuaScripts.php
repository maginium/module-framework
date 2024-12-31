<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache;

/**
 * Class LuaScripts.
 *
 * This class provides reusable Lua scripts to be used within Redis operations.
 * Specifically, it focuses on atomic lock-related operations that ensure safe
 * concurrency control in a distributed system.
 *
 * The provided scripts enable secure management of distributed locks, ensuring that
 * lock acquisition and release operations are atomic and consistent.
 *
 * Usage:
 * - The `releaseLock()` method generates a Lua script that can be executed to release
 *   a lock only if the caller owns the lock. This prevents other processes from accidentally
 *   releasing a lock they do not own.
 *
 * Example Redis Lock Operation:
 *
 * ```lua
 * if redis.call("get", KEYS[1]) == ARGV[1] then
 *     return redis.call("del", KEYS[1])
 * else
 *     return 0
 * end
 * ```
 *
 * - `KEYS[1]`: The Redis key for the lock.
 * - `ARGV[1]`: The unique identifier for the owner trying to release the lock.
 */
class LuaScripts
{
    /**
     * Generate the Lua script to safely release a lock.
     *
     * This script checks if the current owner of the lock matches the provided owner key (ARGV[1]).
     * If the lock is held by the owner, it is deleted from Redis.
     *
     * Key Definitions:
     * KEYS[1] - The Redis key representing the lock.
     * ARGV[1] - The owner identifier attempting to release the lock.
     *
     * @return string The Lua script for releasing a lock atomically.
     */
    public static function releaseLock(): string
    {
        // Lua script for releasing a lock only if the current owner matches the provided owner key.
        return <<<'LUA'
            if redis.call("get", KEYS[1]) == ARGV[1] then
                return redis.call("del", KEYS[1])
            else
                return 0
            end
        LUA;
    }
}
