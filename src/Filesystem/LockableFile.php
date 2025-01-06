<?php

declare(strict_types=1);

namespace Maginium\Framework\Filesystem;

use Exception;
use Illuminate\Contracts\Filesystem\LockTimeoutException;

/**
 * Class LockableFile.
 *
 * Provides functionality for file manipulation with lock support.
 */
class LockableFile
{
    /**
     * The file resource handle.
     *
     * @var resource
     */
    protected $handle;

    /**
     * The file's absolute path.
     *
     * @var string
     */
    protected $path;

    /**
     * Indicates whether the file is currently locked.
     *
     * @var bool
     */
    protected $isLocked = false;

    /**
     * Create a new LockableFile instance.
     *
     * @param string $path The path to the file.
     * @param string $mode The mode in which to open the file.
     *
     * @throws Exception If the file cannot be created.
     */
    public function __construct(string $path, string $mode)
    {
        $this->path = $path;

        // Ensure the directory for the file exists.
        $this->ensureDirectoryExists($path);

        // Create the file resource.
        $this->createResource($path, $mode);
    }

    /**
     * Read the file contents.
     *
     * @param int|null $length The number of bytes to read. If null, read the entire file.
     *
     * @return string The read content.
     */
    public function read(?int $length = null): string
    {
        // Clear cached file statistics to ensure accurate file size.
        clearstatcache(true, $this->path);

        // Read the file contents.
        return fread($this->handle, $length ?? ($this->size() ?: 1));
    }

    /**
     * Get the size of the file in bytes.
     *
     * @return int The file size.
     */
    public function size(): int
    {
        return filesize($this->path);
    }

    /**
     * Write the specified content to the file.
     *
     * @param string $contents The content to write.
     *
     * @return $this
     */
    public function write(string $contents): self
    {
        fwrite($this->handle, $contents);

        // Flush the output to ensure data is written.
        fflush($this->handle);

        return $this;
    }

    /**
     * Truncate the file's contents.
     *
     * @return $this
     */
    public function truncate(): self
    {
        // Move the file pointer to the beginning of the file.
        rewind($this->handle);

        // Truncate the file to zero length.
        ftruncate($this->handle, 0);

        return $this;
    }

    /**
     * Acquire a shared lock on the file.
     *
     * @param bool $block Whether to block until the lock is acquired.
     *
     * @throws LockTimeoutException If the lock cannot be acquired.
     *
     * @return $this
     */
    public function getSharedLock(bool $block = false): self
    {
        if (! flock($this->handle, LOCK_SH | ($block ? 0 : LOCK_NB))) {
            throw new LockTimeoutException("Unable to acquire shared lock at path [{$this->path}].");
        }

        $this->isLocked = true;

        return $this;
    }

    /**
     * Acquire an exclusive lock on the file.
     *
     * @param bool $block Whether to block until the lock is acquired.
     *
     * @throws LockTimeoutException If the lock cannot be acquired.
     *
     * @return $this
     */
    public function getExclusiveLock(bool $block = false): self
    {
        if (! flock($this->handle, LOCK_EX | ($block ? 0 : LOCK_NB))) {
            throw new LockTimeoutException("Unable to acquire exclusive lock at path [{$this->path}].");
        }

        $this->isLocked = true;

        return $this;
    }

    /**
     * Release the current lock on the file.
     *
     * @return $this
     */
    public function releaseLock(): self
    {
        // Release the lock if one is held.
        if ($this->isLocked) {
            flock($this->handle, LOCK_UN);
            $this->isLocked = false;
        }

        return $this;
    }

    /**
     * Close the file resource.
     *
     * @return bool True if the file was closed successfully.
     */
    public function close(): bool
    {
        // Ensure any locks are released before closing.
        if ($this->isLocked) {
            $this->releaseLock();
        }

        // Close the file resource.
        return fclose($this->handle);
    }

    /**
     * Ensure that the directory for the given file path exists.
     *
     * @param string $path The file path.
     *
     * @return void
     */
    protected function ensureDirectoryExists(string $path): void
    {
        $directory = dirname($path);

        // Create the directory recursively if it doesn't exist.
        if (! file_exists($directory)) {
            @mkdir($directory, 0777, true);
        }
    }

    /**
     * Create the file resource.
     *
     * @param string $path The path to the file.
     * @param string $mode The mode in which to open the file.
     *
     * @throws Exception If the file resource cannot be created.
     *
     * @return void
     */
    protected function createResource(string $path, string $mode): void
    {
        $this->handle = fopen($path, $mode);

        if (! $this->handle) {
            throw new Exception("Unable to open file at path [{$path}].");
        }
    }
}
