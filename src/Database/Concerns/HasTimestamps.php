<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Concerns;

/**
 * Trait for managing timestamps.
 *
 * @property string|null $createdAtKey Custom field name for created_at timestamp
 * @property string|null $updatedAtKey Custom field name for updated_at timestamp
 */
trait HasTimestamps
{
    /**
     * Get the creation time.
     *
     * This method returns the created_at timestamp of the model.
     *
     * @return string|null The creation time or null if not set.
     */
    public function getCreatedAt(): string
    {
        return $this->getData(static::getCreatedAtColumn());
    }

    /**
     * Set the creation time.
     *
     * This method sets the created_at timestamp for the model.
     *
     * @param string $createdAt The creation time to set.
     *
     * @return $this The current instance for method chaining.
     */
    public function setCreatedAt($createdAt): self
    {
        $this->setData(static::getCreatedAtColumn(), $createdAt);

        return $this;
    }

    /**
     * Get the update time.
     *
     * This method returns the updated_at timestamp of the model.
     *
     * @return string|null The update time or null if not set.
     */
    public function getUpdatedAt(): string
    {
        return $this->getData(static::getUpdatedAtColumn());
    }

    /**
     * Set the update time.
     *
     * This method sets the updated_at timestamp for the model.
     *
     * @param string $updatedAt The update time to set.
     *
     * @return $this The current instance for method chaining.
     */
    public function setUpdatedAt($updatedAt): self
    {
        $this->setData(static::getUpdatedAtColumn(), $updatedAt);

        return $this;
    }
}
