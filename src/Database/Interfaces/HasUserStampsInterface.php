<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces;

use Maginium\Framework\Database\Interfaces\Data\ModelInterface;

/**
 * Interface HasUserStampsInterface.
 *
 * This interface defines methods for getting and setting user-related properties
 * for models that require user tracking.
 */
interface HasUserStampsInterface
{
    /**
     * @const string CREATED_BY
     * The column name for the user who created the model.
     */
    public const CREATED_BY = 'created_by';

    /**
     * @const string UPDATED_BY
     * The column name for the user who last updated the model.
     */
    public const UPDATED_BY = 'updated_by';

    /**
     * Get the ID of the user who created the model.
     *
     * @return int|null The ID of the creator user.
     */
    public function getCreatedBy(): ?int;

    /**
     * Set the ID of the user who created the model.
     *
     * This method sets the ID of the user responsible for creating this model instance.
     *
     * @param int $userId The ID of the user to set as the creator.
     *
     * @return ModelInterface Returns the current instance for method chaining.
     */
    public function setCreatedBy(int $userId): ModelInterface;

    /**
     * Get the ID of the user who last updated the model.
     *
     * @return int|null The ID of the updater user.
     */
    public function getUpdatedBy(): ?int;

    /**
     * Set the ID of the user who last updated the model.
     *
     * This method sets the ID of the user responsible for last updating this model instance.
     *
     * @param int $userId The ID of the user to set as the updater.
     *
     * @return ModelInterface Returns the current instance for method chaining.
     */
    public function setUpdatedBy(int $userId): ModelInterface;
}
