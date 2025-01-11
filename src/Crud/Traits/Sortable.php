<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Traits;

use Closure;
use Exception;
use Illuminate\Support\Stringable;
use Maginium\Framework\Crud\Exceptions\FieldNotSupported;
use Maginium\Framework\Crud\Helpers\Relation;
use Maginium\Framework\Crud\Sorts\Sort;
use Maginium\Framework\Database\Eloquent\Builder;
use Maginium\Framework\Database\Eloquent\Model;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\Request;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/**
 * Trait that adds sorting functionality to Eloquent models.
 *
 * This trait allows models to support dynamic sorting based on query parameters.
 * It provides a `scopeSort` method to apply sorting to queries, and it can validate
 * and apply sorting fields dynamically based on the parameters passed.
 *
 * @property array $sortFields List of available sortable fields.
 *
 * @mixin Model
 */
trait Sortable
{
    // Includes getColumns trait, presumably to handle column fetching
    use getColumns;

    /**
     * Apply sorts to the query builder instance.
     *
     * This method applies sorting to the query using the `sort` query parameter,
     * which can be passed as an array of field names or a string with a sorting
     * direction (e.g., "field:asc").
     *
     * @param Builder    $query The Eloquent query builder instance.
     * @param array|null $params Optional. The sorting parameters to apply.
     *                            If null, the method will fetch the 'sort' query parameter from the request.
     *
     * @throws Exception If sorting validation fails.
     *
     * @return Builder The modified query builder instance.
     */
    public function scopeSort(Builder $query, ?array $params = null): Builder
    {
        // If no params are passed, fetch the 'sort' query parameter from the request.
        if (! isset($params)) {
            $params = Request::query('sort', []);
        }

        // Ensure params is an array, even if it's a single string.
        if (! Validator::isArray($params)) {
            $params = [$params];
        }

        // Iterate over each field and apply sorting.
        foreach ($params as $field) {
            // Extract the column part of the field, excluding the sort direction.
            $column = Str::of($field)->beforeLast(':');

            // Validate the column before applying sort. Skip if invalid.
            if (! $this->validate($column)) {
                continue;
            }

            // If the column includes a relation, remove the relation part.
            $column = Str::contains($column, '.') ? Str::before($column, '.') : $column;

            // Get the real column name used for sorting.
            $column = $this->getSortField($column);

            // Apply the sort to the query using the resolved column and field.
            $this->applySort($column, $field, $query);
        }

        // Return the modified query builder instance with applied sorts.
        return $query;
    }

    /**
     * Applies the sort logic to the query.
     *
     * @param string  $column The column to apply the sorting on.
     * @param string  $field  The field parameter from the query.
     * @param Builder $query  The Eloquent query builder instance.
     *
     * @return Builder The modified query builder instance.
     */
    public function applySort(string $column, string $field, Builder $query): Builder
    {
        $instance = Container::resolve(Sort::class, ['column' => $column, 'field' => $field, 'query' => $query, 'model' => $this]);

        // Create and apply a Sort instance to handle the sorting logic.
        return $instance();
    }

    /**
     * Retrieve the real name of the sort field.
     *
     * This method retrieves the actual column name for sorting based on
     * the field name passed in the query.
     *
     * @param string $field The field name to resolve.
     *
     * @return string The real column name.
     */
    public function getSortField(string $field): string
    {
        // Return the real column name for the field, based on available sort fields.
        return $this->realName($this->availableSort(), $field);
    }

    /**
     * Set the available sort fields for the model.
     *
     * This method allows you to define which fields are sortable.
     * It can accept either an array of fields or a dynamic field list.
     *
     * @param Builder      $query The Eloquent query builder instance.
     * @param array|string $fields The fields to be sorted.
     *
     * @return Builder The modified query builder instance.
     */
    public function scopeSortFields(Builder $query, array|string $fields): Builder
    {
        // Assign the provided fields to the sortFields property.
        $this->sortFields = Validator::isArray($fields) ? $fields : array_slice(func_get_args(), 1);

        // Return the query builder instance after setting the sort fields.
        return $query;
    }

    /**
     * Validate the provided sorting field.
     *
     * This method ensures that the sorting field is valid and supported
     * by checking against the list of available sort fields.
     *
     * @param string $field The field to validate.
     *
     * @throws FieldNotSupported If the field is not supported for sorting.
     *
     * @return bool Whether the field is valid for sorting.
     */
    private function validate(Stringable $field): bool
    {
        // Get the available sort fields to check against.
        $available = $this->availableSort();

        // Run validation inside the safe closure to handle exceptions.
        return $this->safe(function() use ($field, $available) {
            // Remove relation part from the field if present.
            $field = Str::contains($field, '.') ? Str::before($field, '.') : $field;

            // If the field is not in the available sort fields, throw an exception.
            if (! in_array($field, $available)) {
                throw FieldNotSupported::make($field, self::class, $available);
            }
        });
    }

    /**
     * Get the available sort fields for the model.
     *
     * This method returns the list of fields that can be used for sorting,
     * either from the model's table columns or from related models.
     *
     * @return array List of available fields for sorting.
     */
    private function availableSort(): array
    {
        // Return either the predefined sort fields or dynamic fields from the model's columns and relations.
        return $this->sortFields ?? array_merge($this->getTableColumns(), Relation::getRelations($this));
    }

    /**
     * Run functions with or without exception.
     *
     * This helper method executes a closure and handles exceptions based on
     * the application's configuration for silent error handling.
     *
     * @param Closure $closure The closure to execute.
     *
     * @throws Exception If an error occurs and is not silenced.
     *
     * @return bool Whether the closure executed successfully.
     */
    private function safe(Closure $closure): bool
    {
        try {
            // Attempt to execute the closure.
            $closure();

            return true;
        } catch (Exception $exception) {
            // Otherwise, rethrow the exception.
            throw $exception;
        }
    }
}
