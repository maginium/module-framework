<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Sorts\Strategies;

use Illuminate\Contracts\Database\Query\Builder as BuilderInterface;
use Maginium\Framework\Crud\Sorts\AbstractSort;
use Maginium\Framework\Database\Eloquent\Builder;

/**
 * Class BelongsToManySort.
 *
 * A strategy class for applying sorting on "BelongsToMany" relationships in Eloquent models.
 *
 * This class handles sorting logic for a "belongs to many" relationship in Laravel. It retrieves the
 * related models and the pivot table that links them. The sorting is applied based on a given column
 * in the related table, and supports both ascending and descending sorting directions. The query
 * is built by joining the related table and the pivot table and ordering by the desired column.
 */
class BelongsToManySort extends AbstractSort
{
    /**
     * Apply sorting to the query for "BelongsToMany" relationships.
     *
     * This method constructs a query that joins the main model's table, the pivot table, and the
     * related model's table. It then orders the results based on the specified column in the
     * related table, either in ascending or descending order.
     *
     * @return Builder The modified query builder with applied sorting.
     */
    public function apply(): Builder
    {
        // Get the table name of the parent model (the model on which the relation is defined).
        $parentTable = $this->model->getTable();

        // Get the related model through the specified relationship.
        $relatedModel = $this->model->{$this->relationName}()->getRelated();

        // Get the table name of the related model.
        $relatedTable = $relatedModel->getTable();

        // Get the name of the pivot table (this is the table linking the parent and related models).
        $pivotTableName = $this->model->{$this->relationName}()->getTable();

        // Get the qualified column name for the related model's key in the pivot table.
        $qualifiedRelatedPivotKeyName = $this->model->{$this->relationName}()->getQualifiedRelatedPivotKeyName();

        // Get the qualified column name for the foreign key in the pivot table.
        $qualifiedForeignPivotKeyName = $this->model->{$this->relationName}()->getQualifiedForeignPivotKeyName();

        // Get the qualified column name for the parent model's key in the pivot table.
        $qualifiedParentKeyName = $this->model->{$this->relationName}()->getQualifiedParentKeyName();

        // Get the qualified column name for the related model's key in the pivot table.
        $qualifiedRelatedKeyName = $this->model->{$this->relationName}()->getQualifiedRelatedKeyName();

        // Start building the query by selecting all columns from the parent model's table.
        return $this->query
            ->select("{$parentTable}.*") // Select all columns from the parent table.

            // Join the pivot table on the qualified parent key.
            ->join($pivotTableName, $qualifiedParentKeyName, '=', $qualifiedForeignPivotKeyName)

            // Join the related model's table on the related key in the pivot table.
            ->join($relatedTable, $qualifiedRelatedPivotKeyName, '=', $qualifiedRelatedKeyName)

            // Group the results by the parent model's key to ensure correct results.
            ->groupBy($qualifiedParentKeyName)

            // Apply the sorting logic based on the provided direction ('asc' or 'desc').
            ->when(
                $this->direction === 'desc', // If the direction is 'desc' (descending),
                function(BuilderInterface $query) use ($relatedTable) {
                    // Order by the maximum value of the specified column in the related table (descending).
                    /** @var Builder $query */
                    $query->orderByRaw("max({$relatedTable}.{$this->column}) {$this->direction}");
                },
                function(BuilderInterface $query) use ($relatedTable) {
                    // Otherwise, order by the minimum value of the specified column in the related table (ascending).
                    /** @var Builder $query */
                    $query->orderByRaw("min({$relatedTable}.{$this->column}) {$this->direction}");
                },
            );
    }
}
