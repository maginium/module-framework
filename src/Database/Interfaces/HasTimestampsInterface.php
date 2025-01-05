<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces;

use Maginium\Framework\Database\Interfaces\Data\ModelInterface;

/**
 * Interface HasTimestampsInterface.
 *
 * This interface defines methods for getting and setting timestamp properties
 * for models that require timestamps.
 */
interface HasTimestampsInterface
{
    /**
     * Get the creation time of the model.
     *
     * @return string The creation time.
     */
    public function getCreatedAt(): string;

    /**
     * Set the creation time of the model.
     *
     * @param string $creationTime The creation time to set.
     *
     * @return ModelInterface
     */
    public function setCreatedAt($creationTime);

    /**
     * Get the update time of the model.
     *
     * @return string The update time.
     */
    public function getUpdatedAt(): string;

    /**
     * Set the update time of the model.
     *
     * @param string $updateTime The update time to set.
     *
     * @return ModelInterface
     */
    public function setUpdatedAt($updateTime);
}
