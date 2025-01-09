<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\DSL;

use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Carbon;
use Maginium\Framework\Support\Str;

/**
 * Trait IndexInterpreter.
 *
 * Provides utility methods for interpreting and constructing Elasticsearch index configurations,
 * including index mapping, analyzer settings, and data cleanup.
 */
trait IndexInterpreter
{
    /**
     * Builds the index mapping parameters for an Elasticsearch index.
     *
     * @param string|null $index The name of the index (optional).
     * @param array $raw The raw configuration data, including settings, mappings, and properties.
     *
     * @return array The formatted index mapping parameters.
     */
    public function buildIndexMap(?string $index, array $raw): array
    {
        $params = [];

        // Add index name to the parameters if provided.
        if ($index) {
            $params['index'] = $index;
        }

        // Add index settings if provided.
        if (! empty($raw['settings'])) {
            $params['body']['settings'] = $raw['settings'];
        }

        // Add mappings to the index if provided.
        if (! empty($raw['map'])) {
            foreach ($raw['map'] as $key => $value) {
                $params['body']['mappings'][$key] = $value;
            }
        }

        // Process and add property mappings.
        if (! empty($raw['properties'])) {
            $properties = [];

            foreach ($raw['properties'] as $prop) {
                $field = $prop['field'];
                unset($prop['field']);

                if (! empty($properties[$field])) {
                    $type = $prop['type'];

                    // Add nested fields for the same property.
                    foreach ($prop as $key => $value) {
                        $properties[$field]['fields'][$type][Str::snake($key)] = $value;
                    }
                } else {
                    // Add new property with its attributes.
                    foreach ($prop as $key => $value) {
                        $properties[$field][Str::snake($key)] = $value;
                    }
                }
            }

            if (! empty($properties)) {
                $params['body']['mappings']['properties'] = $properties;
            }
        }

        return $params;
    }

    /**
     * Builds analyzer settings for an Elasticsearch index.
     *
     * @param string $index The name of the index.
     * @param array $raw The raw configuration data, including analysis settings.
     *
     * @return array The formatted analyzer settings.
     */
    public function buildAnalyzerSettings(string $index, array $raw): array
    {
        $params = ['index' => $index];
        $analysis = [];

        // Process raw analysis settings.
        if (! empty($raw)) {
            foreach ($raw['analysis'] as $setting) {
                $config = $setting['config'];
                $name = $setting['name'];
                unset($setting['config'], $setting['name']);

                if (! empty($setting)) {
                    foreach ($setting as $key => $value) {
                        $analysis[$config][$name][$key] = $value;
                    }
                }
            }
        }

        $params['body']['settings']['analysis'] = $analysis;

        return $params;
    }

    /**
     * Filters and retrieves Elasticsearch indices based on visibility.
     *
     * @param array $data The list of indices.
     * @param bool $all Whether to include all indices, including system indices (default: false).
     *
     * @return array The filtered list of indices.
     */
    public function catIndices(array $data, bool $all = false): array
    {
        if (! $all && ! empty($data)) {
            $indices = $data;
            $data = [];

            // Filter out system indices (those starting with '.').
            foreach ($indices as $index) {
                if (! str_starts_with($index['index'], '.')) {
                    $data[] = $index;
                }
            }
        }

        return $data;
    }

    /**
     * Cleans data recursively by converting Carbon instances to ISO 8601 strings.
     *
     * @param array $data The data to clean.
     *
     * @return array The cleaned data.
     */
    public function cleanData(array $data): array
    {
        if (! empty($data)) {
            Arr::walk_recursive($data, function(&$item) {
                if ($item instanceof Carbon) {
                    // Convert Carbon instances to ISO 8601 format.
                    $item = $item->toIso8601String();
                }
            });
        }

        return $data;
    }
}
