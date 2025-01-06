<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Helpers;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Maginium\Framework\Elasticsearch\Eloquent\Models;
use Maginium\Framework\Support\Reflection;

/**
 * Trait QueriesRelationships.
 *
 * This trait provides methods for querying relationships in Eloquent models.
 * It offers functionality to add conditions to check the existence or count of related models.
 * It also handles special cases like hybrid queries that involve models across different database connections.
 * The primary methods include `has` for checking existence and count, and `addHybridHas` for handling complex cases.
 */
trait QueriesRelationships
{
    /**
     * Add a relationship count / exists condition to the query.
     *
     * This method allows you to add a condition to the query that checks whether a given relationship exists
     * and meets a specific count condition. It supports both standard relationships as well as "hybrid" relationships
     * that span multiple database connections. The method is flexible in how it handles counting,
     * supporting both direct counts and existence checks.
     *
     * @param  Relation|string  $relation  The relationship to check (can be a string representing the relation name or a Relation instance)
     * @param  string  $operator  The operator to use for comparison (default is '>=')
     * @param  int  $count  The number to compare against (default is 1)
     * @param  string  $boolean  The logical operator to combine multiple conditions ('and' or 'or', default is 'and')
     * @param  Closure|null  $callback  An optional callback to add additional conditions to the query
     *
     * @throws Exception  If any error occurs while handling the relationship or adding the condition
     *
     * @return Builder|static  Returns the updated query builder instance with the condition added
     */
    public function has(
        $relation,
        $operator = '>=',
        $count = 1,
        $boolean = 'and',
        ?Closure $callback = null,
    ): Builder|static {
        // Handle nested relations by calling `hasNested` if the relation name contains a dot
        if (is_string($relation) && str_contains($relation, '.')) {
            return $this->hasNested($relation, $operator, $count, $boolean, $callback);
        }

        // Resolve the relation if it's a string (i.e., relation name)
        if (is_string($relation)) {
            $relation = $this->getRelationWithoutConstraints($relation);
        }

        // Handle hybrid relations that involve multiple database connections
        if ($this->getModel() instanceof Model || $this->isAcrossConnections($relation)) {
            return $this->addHybridHas($relation, $operator, $count, $boolean, $callback);
        }

        // Determine if we should use a subquery for exists or count depending on the operator and count
        $method = $this->canUseExistsForExistenceCheck($operator, $count)
            ? 'getRelationExistenceQuery'
            : 'getRelationExistenceCountQuery';

        // Build the query for the relation
        $hasQuery = $relation->{$method}($relation->getRelated()->newQuery(), $this);

        // Apply the callback as a scope to allow custom logical grouping
        if ($callback) {
            $hasQuery->callScope($callback);
        }

        // Finalize and return the query with the additional `has` condition
        return $this->addHasWhere($hasQuery, $relation, $operator, $count, $boolean);
    }

    /**
     * Add a hybrid relationship count condition to the query across different database connections.
     *
     * In cases where relationships span across multiple databases or connections, we cannot use a normal
     * subquery with `whereExists`. Instead, we handle the query with a `whereIn` clause by fetching the related
     * IDs and comparing them against the given conditions.
     *
     * @param  Relation  $relation  The relation instance
     * @param  string  $operator  The operator to compare against (default is '>=')
     * @param  int  $count  The number of related records to compare against (default is 1)
     * @param  string  $boolean  Logical operator for combining conditions ('and' or 'or', default is 'and')
     * @param  Closure|null  $callback  An optional callback to modify the query further
     *
     * @throws Exception  If the query cannot be processed correctly
     *
     * @return mixed  The result of the hybrid relationship condition query
     */
    public function addHybridHas(
        Relation $relation,
        string $operator = '>=',
        int $count = 1,
        string $boolean = 'and',
        ?Closure $callback = null,
    ): mixed {
        $hasQuery = $relation->getQuery();

        // Apply the callback to the query if provided
        if ($callback) {
            $hasQuery->callScope($callback);
        }

        // Determine if the query should use `whereNotIn` based on the operator
        $not = in_array($operator, ['<', '<=', '!=']);

        // If comparing to 0, flip the `not` condition
        if ($count === 0) {
            $not = ! $not;
        }

        // Retrieve the related IDs to be compared
        $relations = $hasQuery->pluck($this->getHasCompareKey($relation));

        // Get the constrained related IDs based on the operator and count
        $relatedIds = $this->getConstrainedRelatedIds($relations, $operator, $count);

        // Apply a `whereIn` query to filter the results by related IDs
        return $this->whereIn($this->getRelatedConstraintKey($relation), $relatedIds, $boolean, $not);
    }

    /**
     * Determine if the relationship spans across multiple database connections.
     *
     * This method checks if the relationship involves models that are located in different database connections
     * by comparing the connection names of the parent and related models.
     *
     * @param  Relation  $relation  The relationship to check
     *
     * @return bool  Returns true if the relation spans multiple connections, otherwise false
     */
    protected function isAcrossConnections(Relation $relation): bool
    {
        return $relation->getParent()->getConnectionName() !== $relation->getRelated()->getConnectionName();
    }

    /**
     * Get the key used for comparing related models in the query.
     *
     * This method determines which key should be used to perform the comparison for the `has` query.
     * It checks the type of the relationship and retrieves the appropriate key, whether it's the foreign
     * key or the owner key.
     *
     * @param  Relation  $relation  The relation instance
     *
     * @return string  The key used for comparison in the query
     */
    protected function getHasCompareKey(Relation $relation): string
    {
        if (Reflection::methodExists($relation, 'getHasCompareKey')) {
            return $relation->getHasCompareKey();
        }

        // Return the appropriate key based on the type of the relation (HasOneOrMany or others)
        return $relation instanceof HasOneOrMany ? $relation->getForeignKeyName() : $relation->getOwnerKeyName();
    }

    /**
     * Get the related IDs constrained by the operator and count.
     *
     * This method processes the related IDs and filters them based on the given operator and count. It also
     * handles converting object IDs to strings to ensure accurate comparisons. The method supports various
     * operators like greater than, less than, and equality.
     *
     * @param  mixed  $relations  The related models' IDs
     * @param  string  $operator  The operator used for comparison
     * @param  int  $count  The count value to compare against
     *
     * @return array  An array of filtered related IDs
     */
    protected function getConstrainedRelatedIds($relations, $operator, $count): array
    {
        // Count the occurrences of each related ID
        $relationCount = array_count_values(array_map(function($id) {
            return (string)$id; // Convert object IDs to strings
        }, is_array($relations) ? $relations : $relations->flatten()->toArray()));

        // Filter out relations based on the operator and count
        $relationCount = array_filter($relationCount, function($counted) use ($count, $operator) {
            if ($count === 0) {
                return true; // If the count is 0, include all results
            }

            // Apply the operator-based filter
            switch ($operator) {
                case '>=':
                case '<':
                    return $counted >= $count;

                case '>':
                case '<=':
                    return $counted > $count;

                case '=':
                case '!=':
                    return $counted === $count;
            }
        });

        // Return the keys (related IDs) after filtering
        return array_keys($relationCount);
    }

    /**
     * Get the key used to constrain the parent model's query.
     *
     * This method returns the appropriate key for constraining the parent model's query. Depending on the
     * type of relation (HasOneOrMany, BelongsTo, etc.), it returns the relevant foreign or local key.
     *
     * @param  Relation  $relation  The relation instance
     *
     * @throws Exception  If the relation is not supported for hybrid queries
     *
     * @return string  The key used for the constraint
     */
    protected function getRelatedConstraintKey(Relation $relation): string
    {
        // Determine the constraint key based on the type of relation
        if ($relation instanceof HasOneOrMany) {
            return $relation->getLocalKeyName();
        }

        if ($relation instanceof BelongsTo) {
            return $relation->getForeignKeyName();
        }

        if ($relation instanceof BelongsToMany && ! $this->isAcrossConnections($relation)) {
            return $this->model->getKeyName();
        }

        // Throw an exception if the relation type is not supported
        throw new Exception(class_basename($relation) . ' is not supported for hybrid query constraints.');
    }
}
