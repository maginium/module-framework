<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Traits;

/**
 * Trait Identifiable.
 *
 * This trait provides functionality to automatically generate and manage IDs for models,
 * including UUIDs and standard identifiers. It includes methods for getting and setting the ID.
 *
 * @property string|null $id The ID of the model (could be UUID or other format).
 * @property string|null $idKey The name of the field where the ID will be stored (e.g., 'id').
 */
trait Identifiable
{
    /**
     * Get the ID for the model.
     *
     * This method retrieves the ID from the model's data storage.
     * If the ID is not set, it returns null.
     *
     * @return int|null The ID as a string, or null if not set.
     */
    public function getId()
    {
        return (int)$this->getData($this->getKeyname()) ?? null;
    }

    /**
     * Set the ID for the model.
     *
     * This method allows setting a specific ID value in the model's data storage.
     *
     * @param int $id The ID to be set.
     *
     * @return $this The current instance of the model for method chaining.
     */
    public function setId($id)
    {
        // Assign the ID to the model.
        $this->setData($this->getKeyname(), $id);

        return $this;
    }
}
