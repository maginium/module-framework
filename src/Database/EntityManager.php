<?php

declare(strict_types=1);

namespace Maginium\Framework\Database;

use Exception;
use LogicException;
use Magento\Framework\EntityManager\EntityManager as BaseEntityManager;

/**
 * Custom Entity Manager for managing model operations.
 *
 * This class extends Magento's base EntityManager to add custom query-building functionality,
 * allowing for dynamic and flexible operations on models while maintaining compatibility
 * with Magento's model management framework.
 */
class EntityManager extends BaseEntityManager
{
    /**
     * Save the model.
     *
     * @param object $model  The model to be saved (either an Eloquent or Magento model)
     * @param array $arguments Additional arguments for the save operation (optional)
     *
     * @throws LogicException If the save operation fails for non-Eloquent models
     * @throws Exception If the save operation fails for Eloquent models
     *
     * @return object The saved model
     */
    public function save($model, $arguments = [])
    {
        // Check if the model is an instance of EloquentModel (Laravel Eloquent model)
        if ($model instanceof EloquentModel) {
            try {
                // Attempt to save the Eloquent model. Use save() instead of create() since create() is for instantiating models
                return $model->save();
            } catch (Exception $e) {
                // Catch any exception during the Eloquent save operation and throw a new Exception with the error message
                throw new Exception('Error saving Eloquent model: ' . $e->getMessage());
            }
        }

        // For non-Eloquent models, use the parent class' save method (Magento model saving)
        return parent::save($model, $arguments);
    }

    /**
     * Delete the model.
     *
     * @param object $model  The model to be deleted (either an Eloquent or Magento model)
     * @param array $arguments Additional arguments for the delete operation (optional)
     *
     * @throws LogicException If the delete operation fails for non-Eloquent models
     * @throws Exception If the delete operation fails for Eloquent models
     *
     * @return bool True if the model was deleted successfully, false otherwise
     */
    public function delete($model, $arguments = [])
    {
        // Check if the model is an instance of EloquentModel (Laravel Eloquent model)
        if ($model instanceof EloquentModel) {
            try {
                // Attempt to delete the Eloquent model
                return $model->delete();
            } catch (Exception $e) {
                // Catch any exception during the Eloquent delete operation and throw a new Exception with the error message
                throw new Exception('Error deleting Eloquent model: ' . $e->getMessage());
            }
        }

        // For non-Eloquent models, use the parent class' delete method (Magento model deletion)
        return parent::delete($model, $arguments);
    }

    /**
     * Load the model by identifier.
     *
     * @param object $model The model to be loaded (either an Eloquent or Magento model)
     * @param string $identifier The identifier to load the model (e.g., model ID)
     * @param array $arguments Additional arguments for the load operation (optional)
     *
     * @throws LogicException If the load operation fails for non-Eloquent models
     * @throws Exception If the load operation fails for Eloquent models
     *
     * @return mixed The loaded model, or null if not found
     */
    public function load($model, $identifier, $arguments = [])
    {
        // Check if the model is an instance of EloquentModel (Laravel Eloquent model)
        if ($model instanceof EloquentModel) {
            try {
                // Attempt to find the model by its identifier using Eloquent's find() method
                return $model->find($identifier);
            } catch (Exception $e) {
                // Catch any exception during the Eloquent load operation and throw a new Exception with the error message
                throw new Exception('Error loading Eloquent model: ' . $e->getMessage());
            }
        }

        // For non-Eloquent models, use the parent class' load method (Magento model loading)
        return parent::load($model, $identifier, $arguments);
    }

    /**
     * Check if the model exists.
     *
     * @param object $model The model to check for existence (either an Eloquent or Magento model)
     *
     * @throws LogicException If the existence check fails for non-Eloquent models
     * @throws Exception If the existence check fails for Eloquent models
     *
     * @return bool True if the model exists, false otherwise
     */
    public function has($model)
    {
        // Check if the model is an instance of EloquentModel (Laravel Eloquent model)
        if ($model instanceof EloquentModel) {
            try {
                // Attempt to check if the Eloquent model exists by checking the 'exists' property
                return $model->exists;
            } catch (Exception $e) {
                // Catch any exception during the Eloquent existence check and throw a new Exception with the error message
                throw new Exception('Error checking existence of Eloquent model: ' . $e->getMessage());
            }
        }

        // For non-Eloquent models, use the parent class' has method (Magento model existence check)
        return parent::has($model);
    }
}
