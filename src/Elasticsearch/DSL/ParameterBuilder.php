<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\DSL;

use stdClass;

/**
 * Class ParameterBuilder.
 *
 * Provides utility methods to build Elasticsearch query and aggregation parameters.
 * Designed to simplify the creation of DSL (Domain-Specific Language) queries and sort logic.
 */
class ParameterBuilder
{
    /**
     * Constructs a match-all query.
     *
     * @return array An Elasticsearch query that matches all documents.
     */
    public static function matchAll(): array
    {
        return [
            'query' => [
                'match_all' => new stdClass, // `stdClass` signifies an empty object in JSON.
            ],
        ];
    }

    /**
     * Builds a field sort query.
     *
     * @param string $field The field to sort by.
     * @param array $payload The sorting options (e.g., order, mode, missing values).
     * @param bool $allowId Whether to allow sorting by the `_id` field (default: false).
     *
     * @return array The Elasticsearch sort query or an empty array if `_id` sorting is disallowed.
     */
    public static function fieldSort(string $field, array $payload, bool $allowId = false): array
    {
        if ($field === '_id' && ! $allowId) {
            // Disallow sorting by `_id` unless explicitly permitted.
            return [];
        }

        if (! empty($payload['is_geo'])) {
            // Handle geospatial sorting.
            return self::fieldSortGeo($field, $payload);
        }

        if (! empty($payload['is_nested'])) {
            // Handle sorting for nested fields.
            return self::filterNested($field, $payload);
        }

        // Default order is ascending.
        $sort = ['order' => $payload['order'] ?? 'asc'];

        if (! empty($payload['mode'])) {
            // Optional mode for multi-valued fields.
            $sort['mode'] = $payload['mode'];
        }

        if (! empty($payload['missing'])) {
            // Handle missing values during sorting.
            $sort['missing'] = $payload['missing'];
        }

        return [$field => $sort];
    }

    /**
     * Builds a geospatial sort query.
     *
     * @param string $field The geospatial field to sort by.
     * @param array $payload The geospatial sorting options (e.g., pin location, order, unit).
     *
     * @return array The Elasticsearch geospatial sort query.
     */
    public static function fieldSortGeo(string $field, array $payload): array
    {
        $sort = [
            $field => $payload['pin'], // Define the geospatial anchor point.
            'order' => $payload['order'] ?? 'asc',
            'unit' => $payload['unit'] ?? 'km', // Default unit is kilometers.
        ];

        if (! empty($payload['mode'])) {
            // Optional mode for multi-valued fields.
            $sort['mode'] = $payload['mode'];
        }

        if (! empty($payload['type'])) {
            // Specify the distance calculation type.
            $sort['distance_type'] = $payload['type'];
        }

        return ['_geo_distance' => $sort];
    }

    /**
     * Builds a nested field sort query.
     *
     * @param string $field The nested field to sort by.
     * @param array $payload The sorting options for the nested field.
     *
     * @return array The Elasticsearch nested field sort query.
     */
    public static function filterNested(string $field, array $payload): array
    {
        // Determine the nested path (e.g., "parent.child").
        $pathParts = explode('.', $field);

        // Use the top-level path for nested sorting.
        $path = $pathParts[0];

        $sort = [
            'order' => $payload['order'] ?? 'asc',
            'nested' => ['path' => $path], // Define the nested path for sorting.
        ];

        if (! empty($payload['mode'])) {
            // Optional mode for nested fields.
            $sort['mode'] = $payload['mode'];
        }

        return [$field => $sort];
    }

    /**
     * Builds multiple aggregations for a field.
     *
     * @param array $aggregations A list of aggregation types (e.g., max, min, avg).
     * @param string $field The field to aggregate.
     *
     * @return array An array of aggregations.
     */
    public static function multipleAggregations(array $aggregations, string $field): array
    {
        $aggs = [];

        foreach ($aggregations as $aggregation) {
            switch ($aggregation) {
                case 'max':
                    $aggs['max_' . $field] = self::maxAggregation($field);

                    break;

                case 'min':
                    $aggs['min_' . $field] = self::minAggregation($field);

                    break;

                case 'avg':
                    $aggs['avg_' . $field] = self::avgAggregation($field);

                    break;

                case 'sum':
                    $aggs['sum_' . $field] = self::sumAggregation($field);

                    break;

                case 'matrix':
                    $aggs['matrix_' . $field] = self::matrixAggregation([$field]);

                    break;

                case 'count':
                    $aggs['count_' . $field] = [
                        'value_count' => ['field' => $field],
                    ];

                    break;
            }
        }

        return $aggs;
    }

    /**
     * Builds a max aggregation query.
     *
     * @param string $field The field to calculate the max value.
     *
     * @return array The max aggregation query.
     */
    public static function maxAggregation(string $field): array
    {
        return ['max' => ['field' => $field]];
    }

    /**
     * Builds a min aggregation query.
     *
     * @param string $field The field to calculate the min value.
     *
     * @return array The min aggregation query.
     */
    public static function minAggregation(string $field): array
    {
        return ['min' => ['field' => $field]];
    }

    /**
     * Builds an average aggregation query.
     *
     * @param string $field The field to calculate the average value.
     *
     * @return array The average aggregation query.
     */
    public static function avgAggregation(string $field): array
    {
        return ['avg' => ['field' => $field]];
    }

    /**
     * Builds a sum aggregation query.
     *
     * @param string $field The field to calculate the sum value.
     *
     * @return array The sum aggregation query.
     */
    public static function sumAggregation(string $field): array
    {
        return ['sum' => ['field' => $field]];
    }

    /**
     * Builds a matrix stats aggregation query.
     *
     * @param array $fields The fields to include in the matrix aggregation.
     *
     * @return array The matrix stats aggregation query.
     */
    public static function matrixAggregation(array $fields): array
    {
        return ['matrix_stats' => ['fields' => $fields]];
    }

    /**
     * Wraps a given query DSL in a query block.
     *
     * @param array $dsl The query DSL to wrap.
     *
     * @return array The wrapped query.
     */
    public static function query(array $dsl): array
    {
        return ['query' => $dsl];
    }
}
