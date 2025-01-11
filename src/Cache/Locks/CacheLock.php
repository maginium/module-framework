<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Locks;

use Illuminate\Contracts\Cache\Store;
use Maginium\Framework\Cache\Lock;
use Maginium\Store\Interfaces\Data\StoreInterface;

/**
 * Class CacheLock.
 */
class CacheLock extends Lock
{
    /**
     * The cache store implementation.
     *
     * @var Store
     */
    protected $store;

    /**
     * Create a new lock instance.
     *
     * @param StoreInterface $store
     * @param string $name
     * @param int $seconds
     * @param string|null $owner
     *
     * @return void
     */
    public function __construct(StoreInterface $store, string $name, int $seconds, ?string $owner = null)
    {
        // Call the parent constructor to initialize the lock's basic properties.
        parent::__construct($name, $seconds, $owner);

        // Store the reference to the ArrayStore where the locks are saved.
        $this->store = $store;
    }

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
        if (method_exists($this->store, 'add') && $this->seconds > 0) {
            return $this->store->add(
                $this->name,
                $this->owner,
                $this->seconds,
            );
        }

        if ($this->store->get($this->name) !== null) {
            return false;
        }

        return ($this->seconds > 0)
                ? $this->store->put($this->name, $this->owner, $this->seconds)
                : $this->store->forever($this->name, $this->owner);
    }

    /**
     * Release the lock.
     *
     * This method releases the lock if the lock exists and is owned by the current process.
     * If the lock cannot be released, it returns false.
     *
     * @return bool True if the lock was released, false otherwise.
     */
    public function release(): bool
    {
        if ($this->isOwnedByCurrentProcess()) {
            return $this->store->forget($this->name);
        }

        return false;
    }

    /**
     * Force release the lock, disregarding ownership.
     *
     * This method directly removes the lock from the store without checking the ownership.
     *
     * @return void
     */
    public function forceRelease(): void
    {
        $this->store->forget($this->name);
    }

    /**
     * Retrieve the current owner of the lock.
     *
     * This method returns the identifier of the current owner of the lock.
     * If the lock does not exist, it returns an empty string.
     *
     * @return string The owner identifier of the lock, or an empty string if the lock does not exist.
     */
    protected function getCurrentOwner(): string
    {
        return $this->store->get($this->name);
    }
}
