<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Traits;

use Exception;
use Maginium\Framework\Crud\Filters\Resolve;
use Maginium\Framework\Database\Eloquent\Builder;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\Request;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/**
 * The List of available filters can be set on the model otherwise it will be read from config.
 *
 * @property array $filters
 *
 * List of available fields, if not declared, will accept everything.
 * @property array $filterFields
 *
 * Fields will restrict to defined filters.
 * @property array $restrictedFilters
 * @property array $renamedFilterFields
 * @property array $userDefinedFilterFields;
 * @property array $sanitizedRestrictedFilters;
 */
trait Filterable
{
    // Includes getColumns trait, presumably to handle column fetching
    use getColumns;

    /**
     * Apply filters to the query builder instance.
     *
     * This method is used to apply the filters passed in the `$params` array to
     * the query builder instance. It allows dynamically filtering the query
     * based on the filter fields defined in the request.
     *
     * @param Builder    $query   The query builder instance to apply filters to.
     * @param array|null $params  The filters to be applied. If not provided, it
     *                            retrieves the filters from the request.
     *
     * @throws Exception Throws an exception if any issues arise while applying filters.
     *
     * @return Builder The query builder instance with the applied filters.
     */
    public function scopeFilter(Builder $query, ?array $params = null): Builder
    {
        // Initialize filter setup (custom logic in bootFilter method)
        /** @var Resolve $resolve */
        $resolve = Container::make(Resolve::class, ['model' => $this]);

        // If no filters are passed, fetch them from the request query parameters
        if (! isset($params)) {
            $params = Request::query('filters', []);
        }

        // Apply each filter to the query builder using the Resolve class
        foreach ($params as $field => $value) {
            // Resolving and applying the filter using the Resolve class
            $resolve->apply($query, $field, $value);
        }

        // Return the query with applied filters
        return $query;
    }

    /**
     * Scope method to filter by specific filters.
     *
     * This method is used to pass an array or list of filter fields to be applied
     * to the query. It allows chaining filters dynamically when querying the database.
     *
     * @param Builder      $query   The query builder instance.
     * @param array|string $filters The filters to be applied. Can be an array or
     *                              a list of arguments.
     *
     * @return Builder The query builder instance with the applied filters.
     */
    public function scopeFilterBy(Builder $query, array|string $filters): Builder
    {
        // Store the filters (either from an array or passed as additional arguments)
        $this->filters = Validator::isArray($filters) ? $filters : Arr::slice(func_get_args(), 1);

        // Return the query builder instance for chaining
        return $query;
    }

    /**
     * Get the real name of the field.
     *
     * This method checks if a filter field has been renamed and returns its
     * real name. If no renaming is defined, it uses the field name as is.
     *
     * @param string $field The field name to retrieve.
     *
     * @return string The real name of the field after any renaming or aliasing.
     */
    public function getField(string $field): string
    {
        // Retrieve the real name of the field from renamed fields and available fields
        return $this->realName(($this->renamedFilterFields ?? []) + $this->availableFields(), $field);
    }

    /**
     * Get the list of available fields for filtering.
     *
     * This method checks if custom fields are provided for filtering. If not,
     * it returns the default fields.
     *
     * @return array The available fields for filtering.
     */
    public function availableFields(): array
    {
        // If neither renamed nor custom filter fields are set, use the default ones
        if (! isset($this->filterFields) && ! isset($this->renamedFilterFields)) {
            return $this->getDefaultFields();
        }

        // Otherwise, return user-defined filter fields
        return $this->getUserDefinedFilterFields();
    }

    /**
     * Get the list of restricted filters.
     *
     * This method retrieves filters that have restrictions applied. The restrictions
     * might involve specific fields or allowed values for filters.
     *
     * @return array The sanitized list of restricted filters.
     */
    public function getRestrictedFilters(): array
    {
        // Return already sanitized restricted filters if available
        if (isset($this->sanitizedRestrictedFilters)) {
            return $this->sanitizedRestrictedFilters;
        }

        $restrictedFilters = [];

        // Process each restricted filter or field to build a sanitized list
        foreach ($this->restrictedFilters ?? $this->filterFields ?? [] as $key => $value) {
            // If the value is a string containing a colon, split it into key-value pairs
            if (Validator::isInt($key) && Str::contains($value, ':')) {
                // Extract key and values from the restricted filter
                $tKey = str($value)->before(':')->squish()->toString();
                $restrictedFilters[$tKey] = str($value)->after(':')->squish()->explode(',')->all();
            }

            // If it's a simple key-value pair, wrap it into an array
            if (Validator::isString($key)) {
                $restrictedFilters[$key] = Arr::wrap($value);
            }
        }

        // Cache the sanitized restricted filters for future use
        return $this->sanitizedRestrictedFilters = $restrictedFilters;
    }

    /**
     * Get the available filters for a specific field.
     *
     * This method retrieves the allowed filters for a specific field. If the field
     * is not restricted, it returns null.
     *
     * @param string $field The field to retrieve available filters for.
     *
     * @return array<int, string>|null The list of filters available for the field, or null.
     */
    public function getAvailableFiltersFor(string $field): ?array
    {
        // Ensure the restricted filters are sanitized
        $this->getRestrictedFilters();

        // Return the filters associated with the given field
        return Arr::get($this->sanitizedRestrictedFilters, $field);
    }

    /**
     * Scope method to filter by specific filter fields.
     *
     * This method allows filtering the query based on specific fields.
     * It adds those fields to the query builder instance dynamically.
     *
     * @param Builder      $query   The query builder instance.
     * @param array|string $fields  The fields to filter by. Can be an array or a
     *                              list of arguments.
     *
     * @return Builder The query builder instance with the applied filter fields.
     */
    public function scopeFilterFields(Builder $query, array|string $fields): Builder
    {
        // Store the filter fields (either from an array or passed as additional arguments)
        $this->filterFields = Validator::isArray($fields) ? $fields : Arr::slice(func_get_args(), 1);

        // Return the query builder instance for chaining
        return $query;
    }

    /**
     * Apply restricted filters to the query builder instance.
     *
     * This method is used to filter results based on specific conditions defined in the
     * `restrictedFilters` property. It can be used to narrow down query results based on
     * these restrictions.
     *
     * @param Builder      $query             The query builder instance
     * @param array|string $restrictedFilters The filters to apply (either as an array or string)
     *
     * @return Builder The modified query builder instance
     */
    public function scopeRestrictedFilters(Builder $query, array|string $restrictedFilters): Builder
    {
        // Convert the input filters into an array if it's not already an array.
        $this->restrictedFilters = Arr::wrap($restrictedFilters);

        // Return the query builder instance for method chaining.
        return $query;
    }

    /**
     * Apply renamed filter fields to the query builder instance.
     *
     * This method allows users to define custom filter names (i.e., rename filters).
     *
     * @param Builder $query               The query builder instance
     * @param array   $renamedFilterFields The renamed filter fields
     *
     * @return Builder The modified query builder instance
     */
    public function scopeRenamedFilterFields(Builder $query, array $renamedFilterFields): Builder
    {
        // Store the renamed filter fields in the property.
        $this->renamedFilterFields = $renamedFilterFields;

        // Return the query builder instance for method chaining.
        return $query;
    }

    /**
     * Get the filters that should be applied.
     *
     * This method either returns the filters defined on the model or defaults to
     * the configuration if no filters are defined.
     *
     * @return array The filters to be used
     */
    private function getFilters(): array
    {
        // Return the filters from the model, or fallback to the config filters.
        return $this->filters;
    }

    /**
     * Get the default fields for filtering.
     *
     * This method returns the default fields, which are a combination of table
     * columns and any related model relations.
     *
     * @return array The default fields
     */
    private function getDefaultFields(): array
    {
        // Merge table columns and related model relations to form the default fields.
        return Arr::merge($this->getTableColumns(), $this->relations());
    }

    /**
     * Get the user-defined filter fields.
     *
     * This method returns the filter fields as defined by the user, using any custom
     * logic for renamed or restricted fields.
     *
     * @return array The user-defined filter fields
     */
    private function getUserDefinedFilterFields(): array
    {
        // If user-defined filter fields are set, return them.
        if (isset($this->userDefinedFilterFields)) {
            return $this->userDefinedFilterFields;
        }

        // If both renamed and filter fields are set, create the user-defined filter fields.
        if (isset($this->renamedFilterFields, $this->filterFields)) {
            // Get the base filter fields.
            $fields = $this->getFilterFields();
            $filterFields = [];

            // Iterate through the fields, applying renaming if necessary.
            foreach ($fields as $filterName) {
                // Check if a filter name is renamed and apply the change.
                if ($columnName = Arr::search($filterName, $this->renamedFilterFields)) {
                    $filterFields[$columnName] = $filterName;
                } else {
                    $filterFields[] = $filterName;
                }
            }

            // Return the user-defined filter fields and store them for future use.
            return $this->userDefinedFilterFields = $filterFields;
        }

        // If only renamed filter fields are set, handle them.
        if (isset($this->renamedFilterFields)) {
            $fields = $this->getDefaultFields();

            $filterFields = [];

            // Iterate through the fields and apply renaming logic.
            foreach ($fields as $filterName) {
                if (Arr::keyExists($filterName, $this->renamedFilterFields)) {
                    $filterFields[$filterName] = $this->renamedFilterFields[$filterName];
                } else {
                    $filterFields[] = $filterName;
                }
            }

            return $this->userDefinedFilterFields = $filterFields;
        }

        // If no custom logic applies, return the default filter fields.
        return $this->userDefinedFilterFields = $this->getFilterFields();
    }

    /**
     * Get the filter fields.
     *
     * This method processes the filter fields by checking if they need renaming
     * or if they are just simple string values.
     *
     * @return array The processed filter fields
     */
    private function getFilterFields(): array
    {
        $userDefinedFilterFields = [];

        // Iterate through the filter fields and process each one.
        foreach ($this->filterFields as $key => $value) {
            // If the filter is an integer (index), handle it as a string.
            if (Validator::isInt($key)) {
                if (Str::contains($value, ':')) {
                    // Remove the suffix from filter names, if any.
                    $userDefinedFilterFields[] = str($value)->before(':')->squish()->toString();
                } else {
                    // Otherwise, just add the filter name.
                    $userDefinedFilterFields[] = $value;
                }
            } else {
                // For associative filter fields, use the key as the filter name.
                $userDefinedFilterFields[] = $key;
            }
        }

        return $userDefinedFilterFields;
    }

    /**
     * List the model's relationships.
     *
     * This method uses reflection to examine the methods of the model and identify
     * those that return instances of Eloquent relations (such as hasMany, belongsTo, etc.).
     *
     * @return array A list of method names that define model relationships
     */
    private function relations(): array
    {
        // Use reflection to get the methods of the called class.
        $methods = Reflection::getMethods(get_called_class());

        // Filter methods that return instances of relationships.
        return collect($methods)
            ->filter(
                fn($method) => ! empty($method->getReturnType()) &&
                    str_contains(
                        $method->getReturnType(),
                        'Illuminate\Database\Eloquent\Relations',
                    ),
            )
            // Get the names of these relationship methods.
            ->map(fn($method) => $method->name)
            // Return the list of relationship method names.
            ->values()->all();
    }
}
