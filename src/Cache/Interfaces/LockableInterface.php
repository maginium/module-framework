<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Interfaces;

use Illuminate\Contracts\Cache\LockProvider;

/**
 * Interface Lockable.
 *
 * This interface defines the contract for a cache store implementation.
 * It provides methods to interact with the cache, including retrieving and storing items,
 * along with support for cache item expiration and other cache management features.
 * Implementing classes must provide the specific details of how cache operations are performed.
 */
interface LockableInterface extends LockProvider
{
}
