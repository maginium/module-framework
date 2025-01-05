<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces;

/**
 * Interface HasUuidInterface.
 *
 * This interface defines the contract for models that manage UUIDs.
 */
interface HasUuidInterface
{
    /**
     * Get the UUID of the model.
     *
     * @return string|null The UUID of the model or null if not set.
     */
    public function getUuid(): ?string;

    /**
     * Set the UUID of the model.
     *
     * @param string $uuid The UUID to set.
     *
     * @return self The instance of the model for method chaining.
     */
    public function setUuid(string $uuid);

    /**
     * Get the name of the UUID field.
     *
     * @return string The name of the UUID field.
     */
    public function getUuidFieldName(): string;
}
