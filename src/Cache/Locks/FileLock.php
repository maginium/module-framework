<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Locks;

/**
 * Class FileLock.
 *
 * This class represents a lock that is stored in afile.
 * It extends the Lock class and provides the implementation for acquiring,
 */
class FileLock extends CacheLock
{
    /**
     * Attempt to acquire the lock.
     *
     * This method attempts to acquire the lock by checking if the lock already exists
     * and if the expiration time is in the future. If the lock exists and is still valid,
     * the lock acquisition fails. Otherwise, it sets the lock with the expiration time.
     *
     * @return bool True if the lock was successfully acquired, false otherwise.
     */
    public function acquire(): bool
    {
        return $this->store->add($this->name, $this->owner, $this->seconds);
    }
}
