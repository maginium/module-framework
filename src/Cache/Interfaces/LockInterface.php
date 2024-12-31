<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Interfaces;

use Illuminate\Contracts\Cache\Lock as LockContract;

/**
 * Interface LockInterface.
 *
 * This interface extends the Illuminate Cache Lock contract to provide additional methods
 * and structure for implementing locking functionality in the Maginium Framework.
 * Any class implementing this interface should adhere to the contract provided by Laravel's cache lock.
 */
interface LockInterface extends LockContract
{
}
