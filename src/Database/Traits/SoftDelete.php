<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Maginium\Framework\Database\Scopes\SoftDeleteScope;
use Maginium\Framework\Support\Reflection;

/**
 * SoftDelete trait for flagging models as deleted instead of actually deleting them.
 */
trait SoftDelete
{
    /**
     * @var bool forceDeleting indicates if the model is currently force deleting.
     */
    protected $forceDeleting = false;

    /**
     * bootSoftDelete trait for a model.
     */
    public static function bootSoftDelete()
    {
        static::addGlobalScope(new SoftDeleteScope);

        static::softDeleted(function($model) {
            /*
             * @event model.afterTrash
             * Called after the model is soft deleted (trashed)
             *
             * Example usage:
             *
             *     $model->bindEvent('model.afterTrash', function() use (\October\Rain\Database\Model $model) {
             *         \Log::info("{$model->name} has been trashed!");
             *     });
             *
             */
            $model->fireEvent('model.afterTrash');

            if ($model->methodExists('afterTrash')) {
                $model->afterTrash();
            }
        });

        static::restoring(function($model) {
            /*
             * @event model.beforeRestore
             * Called before the model is restored from a soft delete
             *
             * Example usage:
             *
             *     $model->bindEvent('model.beforeRestore', function() use (\October\Rain\Database\Model $model) {
             *         \Log::info("{$model->name} is going to be restored!");
             *     });
             *
             */
            $model->fireEvent('model.beforeRestore');

            if ($model->methodExists('beforeRestore')) {
                $model->beforeRestore();
            }
        });

        static::restored(function($model) {
            /*
             * @event model.afterRestore
             * Called after the model is restored from a soft delete
             *
             * Example usage:
             *
             *     $model->bindEvent('model.afterRestore', function() use (\October\Rain\Database\Model $model) {
             *         \Log::info("{$model->name} has been brought back to life!");
             *     });
             *
             */
            $model->fireEvent('model.afterRestore');

            if ($model->methodExists('afterRestore')) {
                $model->afterRestore();
            }
        });
    }

    /**
     * withTrashed gets a new query builder that includes soft deletes.
     *
     * @return Builder|static
     */
    public static function withTrashed()
    {
        return with(new static)->newQueryWithoutScope(new SoftDeleteScope);
    }

    /**
     * onlyTrashed gets a new query builder that only includes soft deletes.
     *
     * @return Builder|static
     */
    public static function onlyTrashed()
    {
        $instance = new static;

        $column = $instance->getQualifiedDeletedAtColumn();

        return $instance->newQueryWithoutScope(new SoftDeleteScope)->whereNotNull($column);
    }

    /**
     * softDeleted registers a "trashed" model event callback with the dispatcher.
     *
     * @param  Closure|string  $callback
     *
     * @return void
     */
    public static function softDeleted($callback)
    {
        static::registerModelEvent('trashed', $callback);
    }

    /**
     * restoring registers a restoring model event with the dispatcher.
     *
     * @param  Closure|string  $callback
     *
     * @return void
     */
    public static function restoring($callback)
    {
        static::registerModelEvent('restoring', $callback);
    }

    /**
     * restored registers a restored model event with the dispatcher.
     *
     * @param  Closure|string  $callback
     *
     * @return void
     */
    public static function restored($callback)
    {
        static::registerModelEvent('restored', $callback);
    }

    /**
     * isSoftDelete helper method to check if the model is currently
     * being hard or soft deleted, useful in events.
     *
     * @return bool
     */
    public function isSoftDelete()
    {
        return ! $this->forceDeleting;
    }

    /**
     * forceDelete Permanently deletes the model, bypassing soft delete functionality.
     *
     * @return void
     */
    public function forceDelete()
    {
        // Set force delete flag.
        $this->forceDeleting = true;

        // Perform the delete operation.
        $this->delete();

        // Reset force delete flag.
        $this->forceDeleting = false;
    }

    /**
     * restore a soft-deleted model instance.
     *
     * @return bool|null
     */
    public function restore()
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->performRestoreOnRelations();

        $this->{$this->getDeletedAtColumn()} = null;

        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * trashed determines if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function trashed()
    {
        return $this->{$this->getDeletedAtColumn()} !== null;
    }

    /**
     * isSoftDeleteEnabled allows for programmatic toggling.
     *
     * @return bool
     */
    public function isSoftDeleteEnabled()
    {
        return true;
    }

    /**
     * getDeletedAtColumn gets the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumn()
    {
        return Reflection::getConstant(static::class, 'DELETED_AT') ?? 'deleted_at';
    }

    /**
     * getQualifiedDeletedAtColumn gets the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedDeletedAtColumn()
    {
        return $this->qualifyColumn($this->getDeletedAtColumn());
    }

    /**
     * performDeleteOnModel.
     *
     * Handles the deletion process for the model. Supports both hard delete
     * and soft delete, including cascading deletes for related models.
     */
    protected function performDeleteOnModel(): void
    {
        // If force deleting or soft delete is not enabled, perform a hard delete
        if ($this->forceDeleting || ! $this->isSoftDeleteEnabled()) {
            // Cascade delete on related models
            $this->performDeleteOnRelations();

            // Delete the current model
            $this->setKeysForSaveQuery($this->newQuery()->withTrashed())->forceDelete();

            // Mark the model as non-existent
            $this->exists = false;
        } else {
            // Perform cascading soft deletes on related models
            $this->performSoftDeleteOnRelations();

            // Run the soft delete process on the current model
            $this->runSoftDelete();
        }
    }

    /**
     * performSoftDeleteOnRelations.
     *
     * Locates relations flagged for soft delete and performs a cascading soft delete.
     */
    protected function performSoftDeleteOnRelations(): void
    {
        // Get all relation definitions for the model
        $definitions = $this->getRelationDefinitions();

        // Iterate over each relation type and its configurations
        foreach ($definitions as $type => $relations) {
            foreach ($relations as $name => $options) {
                // Skip relations that do not have the softDelete flag enabled
                if (! array_get($options, 'softDelete', false)) {
                    continue;
                }

                // Get the related model or collection
                if (! $relation = $this->{$name}) {
                    continue;
                }

                // If the relation is a single model, delete it
                if ($relation instanceof EloquentModel) {
                    $relation->delete();
                }
                // If the relation is a collection, delete each model
                elseif ($relation instanceof CollectionBase) {
                    $relation->each(fn($model) => $model->delete());
                }
            }
        }
    }

    /**
     * runSoftDelete.
     *
     * Performs the actual soft delete operation on the current model instance.
     */
    protected function runSoftDelete(): void
    {
        // Prepare a query scoped to this model's key
        $query = $this->setKeysForSaveQuery($this->newQuery());

        // Get the current timestamp
        $time = $this->freshTimestamp();

        // Prepare the columns to be updated for soft delete
        $columns = [$this->getDeletedAtColumn() => $this->fromDateTime($time)];

        // Set the deleted_at timestamp on the model instance
        $this->{$this->getDeletedAtColumn()} = $time;

        // If timestamps are enabled, update the updated_at column as well
        if ($this->timestamps && $this->getUpdatedAtColumn() !== null) {
            $this->{$this->getUpdatedAtColumn()} = $time;
            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        // Execute the update query
        $query->update($columns);

        // Sync the model's original attributes with the updated values
        $this->syncOriginalAttributes(array_keys($columns));

        // Fire the 'trashed' model event
        $this->fireModelEvent('trashed', false);
    }

    /**
     * performRestoreOnRelations.
     *
     * Locates relations flagged for soft delete and performs a cascading restore operation.
     */
    protected function performRestoreOnRelations(): void
    {
        // Get all relation definitions for the model
        $definitions = $this->getRelationDefinitions();

        // Iterate over each relation type and its configurations
        foreach ($definitions as $type => $relations) {
            foreach ($relations as $name => $options) {
                // Skip relations that do not have the softDelete flag enabled
                if (! array_get($options, 'softDelete', false)) {
                    continue;
                }

                // Retrieve only trashed (soft-deleted) related models
                $relation = $this->{$name}()->onlyTrashed()->getResults();

                // Skip if no related models are found
                if (! $relation) {
                    continue;
                }

                // If the relation is a single model, restore it
                if ($relation instanceof EloquentModel) {
                    $relation->restore();
                }
                // If the relation is a collection, restore each model
                elseif ($relation instanceof CollectionBase) {
                    $relation->each(fn($model) => $model->restore());
                }
            }
        }
    }
}
