<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Stores;

use DateInterval;
use DateTimeInterface;
use Exception;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Filesystem\LockTimeoutException;
use Illuminate\Support\InteractsWithTime;
use Magento\Framework\App\CacheInterface;
use Maginium\Framework\Cache\Interfaces\LockableInterface;
use Maginium\Framework\Cache\Interfaces\StoreInterface;
use Maginium\Framework\Cache\Locks\FileLockFactory;
use Maginium\Framework\Cache\RetrievesMultipleKeys;
use Maginium\Framework\Filesystem\Filesystem;
use Maginium\Framework\Filesystem\LockableFileFactory;
use Maginium\Framework\Serializer\Facades\Serializer;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Path;

/**
 * Class FileStore.
 *
 * An implementation of a cache store that uses an array as the underlying storage.
 * This class supports cache locking and tagging functionality, allowing for
 * efficient management of cache items in-memory.
 *
 * It extends TaggableStore to provide cache tagging features and implements
 * LockableInterface to allow cache locking functionality, using Laravel's cache locking system.
 */
class FileStore implements LockableInterface, StoreInterface
{
    use InteractsWithTime;
    use RetrievesMultipleKeys {
        putMany as private putManyAlias;
    }

    /**
     * The file cache directory.
     *
     * @var string
     */
    protected $directory;

    /**
     * The file cache lock directory.
     *
     * @var string|null
     */
    protected $lockDirectory;

    /**
     * Cache client instance for handling cache operations.
     *
     * @var CacheInterface
     */
    protected CacheInterface $cache;

    /**
     * The factory responsible for creating file lock instances.
     */
    protected FileLockFactory $fileLockFactory;

    /**
     * The factory responsible for creating lockable file instances.
     */
    protected LockableFileFactory $lockableFilekFactory;

    /**
     * Instance for creating filesystem instances.
     *
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * Create a new file cache store instance.
     *
     * @param string $directory
     * @param  Filesystem  $filesystem Instance for creating filesystem instances.
     * @param CacheInterface $cache The cache client instance used to handle cache operations.
     * @param FileLockFactory $fileLockFactory The factory responsible for creating file lock instances.
     * @param LockableFileFactory $lockableFilekFactory The factory responsible for creating lockable file instances.
     *
     * @return void
     */
    public function __construct(Filesystem $filesystem, CacheInterface $cache, FileLockFactory $fileLockFactory, LockableFileFactory $lockableFilekFactory, string $directory)
    {
        $this->cache = $cache;
        $this->directory = $directory;
        $this->filesystem = $filesystem;
        $this->fileLockFactory = $fileLockFactory;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * This method attempts to retrieve the cached value associated with the specified key.
     * If the item is found, it will be unserialized and returned.
     * If not found, it will return null.
     *
     * @param  array|string  $key The key identifying the cached item.
     *
     * @return mixed Returns the cached value if found or null if not found.
     */
    public function get($key): mixed
    {
        return $this->getPayload($key)['data'] ?? null;
    }

    /**
     * Increment the value of an item in the cache.
     *
     * This method increments the cached value for a specific key by the specified amount.
     * The value is incremented by default by 1 if no value is provided.
     *
     * @param  string  $key The key for the item in the cache.
     * @param  mixed  $value The value to increment by (default is 1).
     *
     * @return int Returns the incremented value after the operation.
     */
    public function increment($key, $value = 1): int
    {
        $raw = $this->getPayload($key);

        return tap(((int)$raw['data']) + $value, function($newValue) use ($key, $raw) {
            $this->put($key, $newValue, $raw['time'] ?? 0);
        });
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * This method reduces the value of a cached item by the given amount.
     * If no value is specified, it will decrement by 1.
     *
     * @param string $key The key for the item in the cache.
     * @param mixed $value The value to decrement by (default is 1).
     *
     * @return int The decremented value.
     */
    public function decrement($key, $value = 1): int
    {
        return $this->increment($key, $value * -1);
    }

    /**
     * Store an item in the cache.
     *
     * This method stores a single value in the cache associated with the provided key.
     * Optionally, you can set a time-to-live (TTL) duration to expire the cache item.
     *
     * @param array|string $key The key identifying the cached item.
     * @param mixed $value The value to store in the cache.
     * @param DateTimeInterface|DateInterval|int|null $ttl The time-to-live duration for the cached item.
     * @param array $tags array of tags to associate with the cached items.
     *
     * @return bool Returns true on success, false on failure.
     */
    public function put($key, $value, $ttl = null, $tags = []): bool
    {
        // Create the file cache directory if necessary.
        $this->ensureCacheDirectoryExists($path = $this->path($key, $tags));

        $result = $this->filesystem->put(
            path: $path,
            contents: $this->expiration($ttl) . Serializer::serialize($value),
            lock: true,
        );

        return (bool)($result !== false && $result > 0);
    }

    /**
     * Store an item in the cache if the key doesn't already exist.
     *
     * This method will store the given value in the cache under the specified key,
     * but only if the key doesn't already exist. It uses a Lua script to check for
     * the existence of the key and sets the value with an expiration time (TTL) if the key
     * is absent. This helps to ensure that the value is only set once.
     *
     * @param string $key The cache key under which the value should be stored.
     * @param mixed $value The value to be stored in the cache.
     * @param int $ttl The time-to-live (TTL) for the cache item in ttl.
     *
     * @return bool Returns true if the item was successfully added, false if the key already exists.
     */
    public function add($key, $value, $ttl): bool
    {
        // Determine the path for the cache file based on the provided key.
        $path = $this->path($key);

        // Ensure the cache directory exists before writing the file.
        $this->ensureCacheDirectoryExists($path);

        // Create a lockable file instance with the appropriate path and mode (read/write/create).
        $file = $this->lockableFilekFactory->create(['path' => $path, 'mode' => 'c+']);

        try {
            // Attempt to acquire an exclusive lock on the file to prevent simultaneous access.
            $file->getExclusiveLock();
        } catch (LockTimeoutException) {
            // If a lock cannot be acquired within the timeout, close the file and return false.
            $file->close();

            return false;
        }

        // Read the first 10 bytes of the file to check for an expiration timestamp.
        $expire = $file->read(10);

        // If the file is empty or the current time exceeds the expiration timestamp:
        if (empty($expire) || $this->currentTime() >= $expire) {
            // Truncate the file, write the new expiration time and serialized value, then close the file.
            $file->truncate()
                ->write($this->expiration($ttl) . Serializer::serialize($value))
                ->close();

            // Return true to indicate that the value was successfully added to the cache.
            return true;
        }

        // If the key exists and has not expired, close the file and return false.
        $file->close();

        return false;
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * This method will store the given value in the cache under the specified key
     * without setting an expiration time, meaning it will persist until it is manually
     * removed from the cache. Optionally, tags can be associated with the cached item
     * for more granular cache invalidation.
     *
     * @param string $key The cache key under which the value should be stored.
     * @param mixed $value The value to be stored in the cache.
     * @param array $tags array of tags to associate with the cached items.
     *
     * @return bool Returns true if the item was successfully stored, false otherwise.
     */
    public function forever($key, $value, $tags = []): bool
    {
        return $this->put($key, $value, 0);
    }

    /**
     * Get a lock instance.
     *
     * This method provides a way to acquire a lock on a given resource. The lock can
     * help prevent race conditions or concurrent access to the resource. The lock
     * can be released manually or automatically after a specified time-to-live (TTL).
     *
     * @param string $name The name of the lock to be acquired.
     * @param int $ttl The time-to-live (TTL) for the lock in ttl.
     * @param string|null $owner The identifier of the owner of the lock.
     *
     * @return Lock An instance of the Lock object that can be used to manage the lock.
     */
    public function lock($name, $ttl = 0, $owner = null): Lock
    {
        // Create the file cache directory if necessary.
        $this->ensureCacheDirectoryExists($this->lockDirectory ?? $this->directory);

        return $this->fileLockFactory->create([
            'name' => $name,
            'owner' => $owner,
            'seconds' => $ttl,
            'store' => $this,
        ]);
    }

    /**
     * Restore a lock instance using the owner identifier.
     *
     * This method is used to restore an existing lock instance based on the owner identifier,
     * allowing the owner to re-acquire or continue holding the lock.
     *
     * @param string $name The name of the lock to be restored.
     * @param string $owner The owner identifier associated with the lock.
     *
     * @return Lock An instance of the Lock object that can be used to manage the restored lock.
     */
    public function restoreLock($name, $owner): Lock
    {
        return $this->lock($name, 0, $owner);
    }

    /**
     * Remove an item from the cache.
     *
     * This method will remove the specified cache item identified by the key. If
     * the item exists in the cache, it will be removed; otherwise, it will return false.
     *
     * @param string $key The cache key of the item to be removed.
     *
     * @return bool Returns true if the item was removed, false if the item doesn't exist.
     */
    public function forget($key): bool
    {
        if ($this->filesystem->exists($file = $this->path($key))) {
            return tap($this->filesystem->delete($file), function($forgotten) use ($key) {
                if ($forgotten && $this->filesystem->exists($file = $this->path("illuminate:cache:flexible:created:{$key}"))) {
                    $this->filesystem->delete($file);
                }
            });
        }

        return false;
    }

    /**
     * Remove all items from the cache.
     *
     * This method will flush the entire cache, removing all stored items. It can be useful
     * for clearing the cache when a complete reset is needed. Be cautious as this will
     * remove all cache entries without any option for selective removal.
     *
     * @return bool Returns true if the cache was successfully flushed.
     */
    public function flush(): bool
    {
        if (! $this->filesystem->isDirectory($this->directory)) {
            return false;
        }

        foreach ($this->filesystem->directories($this->directory) as $directory) {
            $deleted = $this->filesystem->deleteDirectory($directory);

            if (! $deleted || $this->filesystem->exists($directory)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the full path for the given cache key.
     *
     * This method generates a file path for the cache key, optionally including tags to associate
     * the cached item with specific groups. Tags can be used to manage related cache items.
     *
     * @param string $key  The unique cache key.
     * @param array $tags  An array of tags to associate with the cached item.
     *
     * @return string The generated file path for the cache item.
     */
    public function path(string $key, array $tags = []): string
    {
        // Generate a SHA-1 hash of the cache key for consistent file naming.
        $hash = sha1($key);

        // Break the hash into smaller parts (2 characters each) for directory structure.
        $parts = Arr::slice(mb_str_split($hash, 2), 0, 2);

        // If tags are provided, generate a tags-specific subdirectory.
        $tagPath = '';

        if (! empty($tags)) {
            // Create a hash of the tags array for consistent tag-based grouping.
            $tagsHash = sha1(implode(',', $tags));
            $tagPath = Path::join('/tags', $tagsHash);
        }

        // Construct the full path including the base directory, tag directory, and hashed cache key.
        return Path::join($this->directory . $tagPath, implode('/', $parts), $hash);
    }

    /**
     * Get the Filesystem instance.
     *
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * Get the working directory of the cache.
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Set the working directory of the cache.
     *
     * @param  string  $directory
     *
     * @return $this
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Set the cache directory where locks should be stored.
     *
     * @param  string|null  $lockDirectory
     *
     * @return $this
     */
    public function setLockDirectory($lockDirectory)
    {
        $this->lockDirectory = $lockDirectory;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get the cache key prefix used in cache operations.
     *
     * The cache key prefix is a string that is prepended to all cache keys. This helps
     * to avoid key collisions when the same Redis instance is shared among multiple applications.
     *
     * @return string The prefix used for cache keys.
     */
    public function getPrefix(): string
    {
        return '';
    }

    /**
     * Get the Redis connection instance used for cache operations.
     *
     * This method provides access to the underlying cache connection, which can
     * be used to execute additional cache operations.
     *
     * @return CacheInterface The instance of the cache connection.
     */
    public function connection(): CacheInterface
    {
        // Return the cache connection object
        return $this->cache;
    }

    /**
     * Create the file cache directory if necessary.
     *
     * @param  string  $path
     *
     * @return void
     */
    protected function ensureCacheDirectoryExists($path)
    {
        $directory = dirname($path);

        if (! $this->filesystem->exists($directory)) {
            $this->filesystem->makeDirectory($directory);
        }
    }

    /**
     * Retrieve an item and expiry time from the cache by key.
     *
     * @param  string  $key
     *
     * @return array
     */
    protected function getPayload($key)
    {
        $path = $this->path($key);

        // If the file doesn't exist, we obviously cannot return the cache so we will
        // just return null. Otherwise, we'll get the contents of the file and get
        // the expiration UNIX timestamps from the start of the file's contents.
        try {
            if (null === ($contents = $this->filesystem->get($path, true))) {
                return $this->emptyPayload();
            }

            $expire = (int)mb_substr($contents, 0, 10);
        } catch (Exception $e) {
            return $this->emptyPayload();
        }

        // If the current time is greater than expiration timestamps we will delete
        // the file and return null. This helps clean up the old filesystem and keeps
        // this directory much cleaner for us as old filesystem aren't hanging out.
        if ($this->currentTime() >= $expire) {
            $this->forget($key);

            return $this->emptyPayload();
        }

        try {
            $data = Serializer::unserialize(mb_substr($contents, 10));
        } catch (Exception) {
            $this->forget($key);

            return $this->emptyPayload();
        }

        // Next, we'll extract the number of ttl that are remaining for a cache
        // so that we can properly retain the time for things like the increment
        // operation that may be performed on this cache on a later operation.
        $time = $expire - $this->currentTime();

        return compact('data', 'time');
    }

    /**
     * Get a default empty payload for the cache.
     *
     * @return array
     */
    protected function emptyPayload()
    {
        return ['data' => null, 'time' => null];
    }

    /**
     * Get the expiration time based on the given ttl.
     *
     * @param  int  $ttl
     *
     * @return int
     */
    protected function expiration($ttl)
    {
        $time = $this->availableAt($ttl);

        return $ttl === 0 || $time > 9999999999 ? 9999999999 : $time;
    }
}
