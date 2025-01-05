<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces;

/**
 * Interface Identifiable.
 *
 * This interface defines the contract for any model capable of managing an ID.
 * It includes methods for retrieving and setting the ID.
 */
interface IdentifiableInterface
{
    /**
     * Retrieve the ID of the model.
     *
     * @return int|null The ID of the model or null if not set.
     */
    public function getId();

    /**
     * Set the ID for the model.
     *
     * @param int $id The ID to set for the model.
     *
     * @return $this The current instance of the model for method chaining.
     */
    public function setId($id);
}
