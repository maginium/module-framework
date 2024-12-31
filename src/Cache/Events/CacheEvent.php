<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Events;

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
     * @param  string|null  $storeName  The name of the cache store.
     * @param  string  $key  The key of the event.
     * @param  string[]  $tags  The tags assigned to the key.
     */
    public function __construct(?string $storeName, string $key, array $tags = [])
    {
        $this->key = $key;
        $this->tags = $tags;
        $this->storeName = $storeName;
    }

    /**
     * Get the name of the cache store.
     */
    public function getStoreName(): ?string
    {
        return $this->storeName;
    }

    /**
     * Set the name of the cache store.
     *
     *
     * @return $this
     */
    public function setStoreName(?string $storeName): self
    {
        $this->storeName = $storeName;

        return $this;
    }

    /**
     * Get the key of the event.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Set the key of the event.
     *
     *
     * @return $this
     */
    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get the tags assigned to the event.
     *
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Set the tags for the cache event.
     *
     * @param  string[]  $tags
     *
     * @return $this
     */
    public function setTags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Retrieve the event details as an associative array.
     */
    public function toArray(): array
    {
        return [
            'storeName' => $this->storeName,
            'key' => $this->key,
            'tags' => $this->tags,
        ];
    }
}
