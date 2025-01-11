<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Sorts;

use Maginium\Framework\Database\Eloquent\Builder;
use Maginium\Framework\Database\Eloquent\Model;

/**
 * Class AbstractSort.
 *
 * An abstract class for applying sorting to Eloquent queries.
 *
 * This class defines the basic structure and properties for sorting operations in Eloquent queries.
 * It provides a constructor for initializing the sorting parameters, such as the column, direction,
 * query builder instance, model, and optional relation name. The `apply` method must be implemented
 * in subclasses to apply the sorting logic to the query.
 */
abstract class AbstractSort
{
    /**
     * The column to sort by.
     *
     * @var string
     */
    protected string $column;

    /**
     * The direction of the sort (ascending or descending).
     *
     * @var string
     */
    protected string $direction;

    /**
     * The query builder instance to apply sorting to.
     *
     * @var Builder
     */
    protected Builder $query;

    /**
     * The model associated with the query (optional).
     *
     * @var Model|null
     */
    protected ?Model $model = null;

    /**
     * The name of the relation (optional).
     *
     * @var string|null
     */
    protected ?string $relationName = null;

    /**
     * Constructor to initialize sorting parameters.
     *
     * @param string $column The column to sort by.
     * @param Builder $query The query builder instance.
     * @param Model|null $model The model instance (optional).
     * @param string|null $relationName The name of the relation (optional).
     * @param string $direction The direction of the sort (either 'asc' or 'desc').
     */
    public function __construct(
        string $column,
        Builder $query,
        string $direction,
        ?Model $model = null,
        ?string $relationName = null,
    ) {
        $this->query = $query;
        $this->model = $model;
        $this->column = $column;
        $this->direction = $direction;
        $this->relationName = $relationName;
    }

    /**
     * Apply the sorting to the query.
     *
     * This method must be implemented in subclasses to define the sorting logic.
     *
     * @return Builder The modified query builder with applied sorting.
     */
    abstract public function apply(): Builder;
}
