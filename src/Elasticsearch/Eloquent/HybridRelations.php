<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Eloquent;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Maginium\Framework\Database\Query\Builder as QueryBuilder;
use Maginium\Framework\Elasticsearch\Eloquent\Model as EloquentModel;
use Maginium\Framework\Elasticsearch\Relations\BelongsTo;
use Maginium\Framework\Elasticsearch\Relations\HasMany;
use Maginium\Framework\Elasticsearch\Relations\HasOne;
use Maginium\Framework\Elasticsearch\Relations\MorphMany;
use Maginium\Framework\Elasticsearch\Relations\MorphOne;
use Maginium\Framework\Elasticsearch\Relations\MorphTo;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;

/**
 * Trait HybridRelations.
 *
 * This trait provides methods to handle various types of relationships in an Eloquent model for Elasticsearch.
 * It includes support for `hasOne`, `morphOne`, `hasMany`, `morphMany`, `belongsTo`, and `morphTo` relationships
 * while leveraging the custom Elasticsearch relations.
 *
 * It overrides default Eloquent relationship methods to adapt them for use with Elasticsearch, ensuring the
 * appropriate query objects and relation handling mechanisms are used.
 */
trait HybridRelations
{
    /**
     * Define a "has one" relationship.
     *
     * This method defines a "has one" relationship, where the current model is the owner
     * and the related model is linked via a foreign key.
     *
     * @param  string  $related The related model class.
     * @param  string|null  $foreignKey The foreign key on the related model.
     * @param  string|null  $localKey The local key on the current model.
     *
     * @return HasOne
     */
    public function hasOne($related, $foreignKey = null, $localKey = null): HasOne
    {
        // Set the foreign key if not provided
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        // Use Container::make to instantiate the related model
        $instance = Container::make($related);

        // Set the local key if not provided
        $localKey = $localKey ?: $this->getKeyName();

        // Return a new HasOne relationship instance
        return Container::make(HasOne::class, ['query' => $instance->newQuery(), 'parent' => $this, 'foreignKey' => $foreignKey, 'localKey' => $localKey]);
    }

    /**
     * Define a "morph one" relationship.
     *
     * This method defines a "morph one" relationship, where the current model can have
     * one related model of different types, identified by a morph type and id.
     *
     * @param  string  $related The related model class.
     * @param  string  $name The name of the polymorphic relationship.
     * @param  string|null  $type The morph type column.
     * @param  string|null  $id The morph id column.
     * @param  string|null  $localKey The local key on the current model.
     *
     * @return MorphOne
     */
    public function morphOne($related, $name, $type = null, $id = null, $localKey = null): MorphOne
    {
        // Use Container::make to instantiate the related model
        $instance = Container::make($related);

        // Retrieve the morph type and id
        [$type, $id] = $this->getMorphs($name, $type, $id);

        // Set the local key if not provided
        $localKey = $localKey ?: $this->getKeyName();

        // Return a new MorphOne relationship instance
        return Container::make(MorphOne::class, ['query' => $instance->newQuery(), 'parent' => $this, 'type' => $type, 'id' => $id, 'localKey' => $localKey]);
    }

    /**
     * Define a "has many" relationship.
     *
     * This method defines a "has many" relationship, where the current model is the owner
     * and the related model is linked via a foreign key.
     *
     * @param  string  $related The related model class.
     * @param  string|null  $foreignKey The foreign key on the related model.
     * @param  string|null  $localKey The local key on the current model.
     *
     * @return HasMany
     */
    public function hasMany($related, $foreignKey = null, $localKey = null): HasMany
    {
        // Set the foreign key if not provided
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        // Use Container::make to instantiate the related model
        $instance = Container::make($related);

        // Set the local key if not provided
        $localKey = $localKey ?: $this->getKeyName();

        // Return a new HasMany relationship instance
        return Container::make(HasMany::class, ['query' => $instance->newQuery(), 'parent' => $this, 'foreignKey' => $foreignKey, 'localKey' => $localKey]);
    }

    /**
     * Define a "morph many" relationship.
     *
     * This method defines a "morph many" relationship, where the current model can have
     * many related models of different types, identified by a morph type and id.
     *
     * @param  string  $related The related model class.
     * @param  string  $name The name of the polymorphic relationship.
     * @param  string|null  $type The morph type column.
     * @param  string|null  $id The morph id column.
     * @param  string|null  $localKey The local key on the current model.
     *
     * @return MorphMany
     */
    public function morphMany($related, $name, $type = null, $id = null, $localKey = null): MorphMany
    {
        // Use Container::make to instantiate the related model
        $instance = Container::make($related);

        // Retrieve the morph type and id
        [$type, $id] = $this->getMorphs($name, $type, $id);

        // Retrieve the table name for the related model
        $table = $instance->getTable();

        // Set the local key if not provided
        $localKey = $localKey ?: $this->getKeyName();

        // Return a new MorphMany relationship instance
        return Container::make(MorphMany::class, ['query' => $instance->newQuery(), 'parent' => $this, 'type' => $type, 'id' => $id, 'localKey' => $localKey]);
    }

    /**
     * Define a "belongs to" relationship.
     *
     * This method defines a "belongs to" relationship, where the current model is the child
     * and is linked to the related model via a foreign key.
     *
     * @param  string  $related The related model class.
     * @param  string|null  $foreignKey The foreign key on the current model.
     * @param  string|null  $otherKey The foreign key on the related model.
     * @param  string|null  $relation The name of the relationship.
     *
     * @return BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $otherKey = null, $relation = null): BelongsTo
    {
        // Automatically set the relation name if not provided
        if ($relation === null) {
            [$current, $caller] = debug_backtrace(0, 2);
            $relation = $caller['function'];
        }

        // Set the foreign key if not provided
        if ($foreignKey === null) {
            $foreignKey = Str::snake($relation) . '_id';
        }

        // Use Container::make to instantiate the related model
        $instance = Container::make($related);

        // Create a new query for the related model
        $query = $instance->newQuery();

        // Set the other key if not provided
        $otherKey = $otherKey ?: $instance->getKeyName();

        // Return a new BelongsTo relationship instance
        return Container::make(BelongsTo::class, ['query' => $query, 'parent' => $this, 'foreignKey' => $foreignKey, 'otherKey' => $otherKey, 'relation' => $relation]);
    }

    /**
     * Define a "morph to" relationship.
     *
     * This method defines a "morph to" relationship, where the current model can belong to
     * different types of related models, identified by a morph type and id.
     *
     * @param  string|null  $name The name of the polymorphic relationship.
     * @param  string|null  $type The morph type column.
     * @param  string|null  $id The morph id column.
     * @param  string|null  $ownerKey The owner key on the related model.
     *
     * @return MorphTo
     */
    public function morphTo($name = null, $type = null, $id = null, $ownerKey = null): MorphTo
    {
        // Automatically set the name if not provided
        if ($name === null) {
            [$current, $caller] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $name = Str::snake($caller['function']);
        }

        // Retrieve the morph type and id
        [$type, $id] = $this->getMorphs($name, $type, $id);

        // Check if the class exists for the morph type
        if (($class = $this->{$type}) === null) {
            //@phpstan-ignore-next-line
            return Container::make(MorphTo::class, [
                'id' => $id,
                'type' => $type,
                'name' => $name,
                'parent' => $this,
                'ownerKey' => $ownerKey,
                'query' => $this->newQuery(),
            ]);
        }

        // Get the actual class name for the morph type
        $class = $this->getActualClassNameForMorph($class);

        // Instantiate the related model
        $instance = Container::make($class);

        // Set the owner key if not provided
        $ownerKey ??= $instance->getKeyName();

        // Return a new MorphTo relationship instance
        return Container::make(MorphTo::class, [
            'id' => $id,
            'type' => $type,
            'name' => $name,
            'parent' => $this,
            'ownerKey' => $ownerKey,
            'query' => $instance->newQuery(),
        ]);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  QueryBuilder  $query
     *
     * @return EloquentBuilder
     */
    public function newEloquentBuilder($query)
    {
        //@phpstan-ignore-next-line
        if (is_subclass_of($this, EloquentModel::class)) {
            return Container::make(Builder::class, ['query' => $query]);
        }

        // Return the default Eloquent builder instance
        return Container::make(EloquentBuilder::class, ['query' => $query]);
    }

    /**
     * Guess the related model for a "belongs to many" relationship.
     *
     * This method attempts to guess the "belongs to many" related model, and can be overridden
     * by implementing `getBelongsToManyCaller` in the model.
     *
     * @return string The name of the related model.
     */
    protected function guessBelongsToManyRelation(): string
    {
        // Attempt to call getBelongsToManyCaller method if it exists
        if (Reflection::methodExists($this, 'getBelongsToManyCaller')) {
            return $this->getBelongsToManyCaller();
        }

        // Return the parent method if not overridden
        return parent::guessBelongsToManyRelation();
    }
}
