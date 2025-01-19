<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Events;

use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;
use ReflectionClass;

/**
 * Class CacheEvent.
 *
 * Represents a cache event, including the associated cache store name, key, and tags.
 * This abstract class serves as a base for specific cache-related events in the application.
 */
abstract class CacheEvent
{
    /**
     * The name of the cache store.
     */
    private ?string $storeName;

    /**
     * The key of the event.
     */
    private string $key;

    /**
     * The tags that were assigned to the key.
     *
     * @var string[]
     */
    private array $tags;

    /**
     * Create a new event instance.
     *
     * @param string|null $storeName The name of the cache store.
     * @param string      $key       The key of the event.
     * @param string[]    $tags      The tags assigned to the key.
     */
    public function __construct(?string $storeName, string $key, array $tags = [])
    {
        $this->key = $key;
        $this->tags = $tags;
        $this->storeName = $storeName;
    }

    /**
     * Get the name of the cache store.
     *
     * @return string|null The cache store name.
     */
    public function getStoreName(): ?string
    {
        return $this->storeName;
    }

    /**
     * Set the name of the cache store.
     *
     * @param string|null $storeName The cache store name.
     *
     * @return $this The current instance.
     */
    public function setStoreName(?string $storeName): self
    {
        $this->storeName = $storeName;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get the key of the event.
     *
     * @return string The key associated with the event.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Set the key of the event.
     *
     * @param string $key The event key.
     *
     * @return $this The current instance.
     */
    public function setKey(string $key): self
    {
        $this->key = $key;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get the tags assigned to the event.
     *
     * @return string[] The tags assigned to the event.
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Set the tags for the cache event.
     *
     * @param string[] $tags The tags to assign.
     *
     * @return $this The current instance.
     */
    public function setTags(array $tags): self
    {
        $this->tags = $tags;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Retrieve the event details as an associative array.
     *
     * @return array The event details, including store name, key, and tags.
     */
    public function toArray(): array
    {
        return [
            'storeName' => $this->storeName,
            'key' => $this->key,
            'tags' => $this->tags,
        ];
    }

    /**
     * Get the event name based on the class name.
     *
     * This method uses reflection to get the base class name (short name)
     * and converts it to lowercase using the `Str::lower` utility.
     *
     * @return string The lowercase name of the event class.
     */
    public function getName(): string
    {
        // Use ReflectionClass to get the base class name.
        $classname = Reflection::getClassBasename(static::class);

        // Convert the class name to lowercase using Str::lower and return.
        return Str::lower(Str::snake($this->getStoreName() . $classname));
    }
}
