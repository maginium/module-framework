<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Collection;

use Maginium\Framework\Elasticsearch\Meta\QueryMetaData;

/**
 * Trait to add Elasticsearch query-related metadata functionality to a collection.
 * This trait provides various methods to interact with and retrieve information about
 * Elasticsearch query metadata, encapsulated in a QueryMetaData object.
 */
trait ElasticCollectionMeta
{
    /**
     * @var QueryMetaData
     *
     * Private instance of QueryMetaData to store meta information about the query.
     * This includes details like the query execution time, the total number of results,
     * and other metadata related to the Elasticsearch query.
     */
    private QueryMetaData $meta;

    /**
     * Set the query metadata for the collection.
     *
     * @param QueryMetaData $meta  The metadata object containing query information.
     *
     * This method allows you to set the `meta` property with an instance of `QueryMetaData`.
     */
    public function setQueryMeta(QueryMetaData $meta): void
    {
        $this->meta = $meta;
    }

    /**
     * Get the query metadata stored in the collection.
     *
     * @return QueryMetaData  The metadata associated with the query.
     *
     * This method retrieves the `meta` property, which is an instance of `QueryMetaData`.
     */
    public function getQueryMeta(): QueryMetaData
    {
        return $this->meta;
    }

    /**
     * Get the query metadata as an array.
     *
     * @return array  The metadata as an array representation.
     *
     * This method converts the `meta` object to an array using the `asArray()` method
     * from the `QueryMetaData` class.
     */
    public function getQueryMetaAsArray(): array
    {
        return $this->meta->asArray();
    }

    /**
     * Get the DSL (Domain Specific Language) representation of the query.
     *
     * @return array  The DSL array containing query and DSL components.
     *
     * This method returns an array with two keys: 'query' and 'dsl', which are fetched
     * from the `meta` object using `getQuery()` and `getDsl()` methods of `QueryMetaData`.
     */
    public function getDsl(): array
    {
        return [
            'query' => $this->meta->getQuery(), // Get the query part of the DSL
            'dsl' => $this->meta->getDsl(),     // Get the full DSL representation
        ];
    }

    /**
     * Get the 'took' value, which represents the time the query took to execute.
     *
     * @return int  The time in milliseconds.
     *
     * This method retrieves the `took` value from the `meta` object, which indicates
     * how long the query took to execute.
     */
    public function getTook(): int
    {
        return $this->meta->getTook();
    }

    /**
     * Get the shard information from the query metadata.
     *
     * @return mixed  The shard information (could be an array, object, or another structure).
     *
     * This method retrieves the shards data, which indicates the Elasticsearch shards
     * that were involved in the query.
     */
    public function getShards(): mixed
    {
        return $this->meta->getShards();
    }

    /**
     * Get the total number of results from the query metadata.
     *
     * @return int  The total number of results.
     *
     * This method retrieves the total number of results returned by the query from
     * the `meta` object.
     */
    public function getTotal(): int
    {
        return $this->meta->getTotal();
    }

    /**
     * Get the maximum score from the query metadata.
     *
     * @return string  The maximum score as a string.
     *
     * This method retrieves the maximum score from the query's results, representing
     * the highest relevance score assigned to any result.
     */
    public function getMaxScore(): string
    {
        return $this->meta->getMaxScore();
    }

    /**
     * Get the results from the query metadata.
     *
     * @return array  The results array containing the data returned by the query.
     *
     * This method retrieves the results of the query from the `meta` object, which
     * contains the actual data returned by Elasticsearch.
     */
    public function getResults(): array
    {
        return $this->meta->getResults();
    }
}
