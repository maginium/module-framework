<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Meta;

/**
 * Class ModelMetaData.
 *
 * This class stores metadata related to Elasticsearch query results. It includes information such as
 * score, index, sort order, cursor, query, and highlights. The class provides getter and setter methods
 * to access and manipulate these properties.
 */
final class ModelMetaData
{
    /**
     * @var array The query used for the Elasticsearch request.
     */
    public array $_query = [];

    /**
     * @var string The score associated with the result.
     * This represents the relevance score for the document in Elasticsearch.
     */
    private string $score = '';

    /**
     * @var string The Elasticsearch index.
     * This represents the index in which the document was found.
     */
    private string $index = '';

    /**
     * @var mixed The document ID.
     * This represents the unique identifier of the document.
     */
    private mixed $_id = '';

    /**
     * @var array Sorting information.
     * This contains the sorting order based on query parameters.
     */
    private array $sort = [];

    /**
     * @var array The Elasticsearch DSL (Domain Specific Language) query.
     * This represents the DSL query used for retrieving data from Elasticsearch.
     */
    private array $_dsl = [];

    /**
     * @var array Cursor for pagination.
     * This allows paginated results from Elasticsearch, helping to retrieve additional data on subsequent requests.
     */
    private array $cursor = [];

    /**
     * @var array Highlights from the search results.
     * This holds the highlighted snippets from the document matching the query.
     */
    private array $highlights = [];

    /**
     * Constructor to initialize metadata properties.
     *
     * The constructor takes an array of metadata (`$meta`) and assigns the corresponding values
     * to the class properties, if they are set in the input.
     *
     * @param array $meta The metadata array containing Elasticsearch result details.
     */
    public function __construct($meta)
    {
        if (isset($meta['score'])) {
            $this->score = $meta['score'];
        }

        if (isset($meta['index'])) {
            $this->index = $meta['index'];
        }

        if (isset($meta['sort'])) {
            $this->sort = $meta['sort'];
        }

        if (isset($meta['cursor'])) {
            $this->cursor = $meta['cursor'];
        }

        if (isset($meta['_id'])) {
            $this->_id = $meta['_id'];
        }

        if (isset($meta['_query'])) {
            $this->_query = $meta['_query'];
        }

        if (isset($meta['dsl'])) {
            $this->_dsl = $meta['dsl'];
        }

        if (isset($meta['highlights'])) {
            $this->highlights = $meta['highlights'];
        }
    }

    /**
     * Get the document ID.
     *
     * Returns the document ID, or null if not set.
     *
     * @return mixed The document ID.
     */
    public function getId(): mixed
    {
        return $this->_id ?? null;
    }

    /**
     * Get the Elasticsearch index.
     *
     * @return string The Elasticsearch index.
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Get the score of the document.
     *
     * @return string The score.
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Get the sorting information.
     *
     * @return array|null The sort information, or null if not set.
     */
    public function getSort(): ?array
    {
        return $this->sort;
    }

    /**
     * Get the cursor for pagination.
     *
     * @return array|null The cursor, or null if not set.
     */
    public function getCursor(): ?array
    {
        return $this->cursor;
    }

    /**
     * Get the query associated with the Elasticsearch request.
     *
     * @return array The query.
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Get the highlights of the document.
     *
     * @return array The highlights, or an empty array if none are set.
     */
    public function getHighlights(): array
    {
        return $this->highlights ?? [];
    }

    /**
     * Parse the highlights and merge them into a nested array structure.
     *
     * @param array $data The data to merge highlights into.
     *
     * @return object|null The merged highlights as an object, or null if no highlights are set.
     */
    public function parseHighlights($data = []): ?object
    {
        if ($this->highlights) {
            $this->_mergeFlatKeysIntoNestedArray($data, $this->highlights);

            return (object)$data;
        }

        return null;
    }

    /**
     * Return the metadata as an associative array.
     *
     * @return array The metadata array containing all the properties.
     */
    public function asArray(): array
    {
        return [
            'score' => $this->score,
            'index' => $this->index,
            '_id' => $this->_id,
            'sort' => $this->sort,
            'cursor' => $this->cursor,
            '_query' => $this->_query,
            '_dsl' => $this->_dsl,
            'highlights' => $this->highlights,
        ];
    }

    /**
     * Set the document ID.
     *
     * @param mixed $id The document ID.
     */
    public function setId($id): void
    {
        $this->_id = $id;
    }

    /**
     * Set the sort information.
     *
     * @param array $sort The sort information.
     */
    public function setSort(array $sort): void
    {
        $this->sort = $sort;
    }

    /**
     * Set the cursor for pagination.
     *
     * @param array $cursor The cursor for pagination.
     */
    public function setCursor(array $cursor): void
    {
        $this->cursor = $cursor;
    }

    /**
     * Merge flat keys into a nested array.
     *
     * This helper method is used to process the highlights and restructure them
     * into a nested array format. It takes each key, splits it by periods (`.`),
     * and inserts the value into the appropriate nested level in the `$data` array.
     *
     * @param array &$data The data array to merge highlights into.
     * @param array $attrs The highlights to process and merge.
     */
    private function _mergeFlatKeysIntoNestedArray(&$data, $attrs): void
    {
        foreach ($attrs as $key => $value) {
            if ($value) {
                // Convert the value to a string if it's an array
                $value = implode('......', $value);
                $parts = explode('.', $key);  // Split the key into parts based on '.'
                $current = &$data;

                // Traverse the parts of the key and create nested structure
                foreach ($parts as $partIndex => $part) {
                    if ($partIndex === count($parts) - 1) {
                        // Set the final value in the nested array
                        $current[$part] = $value;
                    } else {
                        // Ensure the part exists and is an array
                        if (! isset($current[$part]) || ! is_array($current[$part])) {
                            $current[$part] = [];
                        }
                        $current = &$current[$part];
                    }
                }
            }
        }
    }
}
