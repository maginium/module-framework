<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces;

use DateTimeInterface;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;

/**
 * Interface HasSoftDeletesInterface.
 *
 * This interface defines methods for getting and setting soft delete-related properties
 * for models that require soft delete functionality.
 */
interface HasSoftDeletesInterface
{
    /**
     * @const string DELETED_AT
     * The column name for the timestamp of when the model was soft deleted.
     */
    public const DELETED_AT = 'deleted_at';

    /**
     * @const string DELETED_BY
     * The column name for the ID of the user who soft deleted the model.
     */
    public const DELETED_BY = 'deleted_by';

    /**
     * Get the timestamp of when the model was soft deleted.
     *
     * @return DateTimeInterface|null The timestamp of the soft deletion.
     */
    public function getDeletedAt(): ?ModelInterface;

    /**
     * Set the timestamp of when the model was soft deleted.
     *
     * @param DateTimeInterface|null $deletedAt The timestamp to set for soft deletion.
     *
     * @return ModelInterface
     */
    public function setDeletedAt(?DateTimeInterface $deletedAt): ModelInterface;

    /**
     * Get the ID of the user who soft deleted the model.
     *
     * @return int|null The ID of the user who performed the soft delete.
     */
    public function getDeletedBy(): ?int;

    /**
     * Set the ID of the user who soft deleted the model.
     *
     * @param int $userId The ID of the user to set as the deleter.
     *
     * @return ModelInterface
     */
    public function setDeletedBy(int $userId): ModelInterface;
}
