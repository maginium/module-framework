<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces;

use Maginium\Framework\Database\Enums\SearcherEngines;

/**
 * Interface SearchableInterface.
 *
 * Defines the contract for models that need to be indexed and searchable.
 * Any model implementing this interface should provide methods for retrieving its index name, key, and the data to be indexed.
 */
interface SearchableInterface
{
    /**
     * Retrieve the unique identifier for the searchable model.
     *
     * This identifier is used to uniquely reference the model within the search index.
     * For example, it could represent a SKU, ID, or any unique value that distinguishes the model.
     *
     * @return string|null The searchable identifier or null if none is set.
     */
    public function searchableIdentifier(): ?string;

    /**
     * Retrieve the index name for the searchable model.
     *
     * This name corresponds to the search index under which the model's data will be indexed and searched.
     *
     * @return string|null The name of the search index or null if none is set.
     */
    public function searchableAs(): ?string;

    /**
     * Get the index name for the model when indexing.
     *
     * @return string
     */
    public function indexableAs(): string;

    /**
     * Get the array of data that should be indexed for the model.
     *
     * This method allows the model to define what data should be indexed.
     * Typically, this will return a key-value array where each key is the name of an attribute and each value is the value to be indexed.
     *
     * @return array<string> Associative array of searchable data.
     */
    public function toSearchableArray(): array;

    /**
     * Get the search engine or service used for indexing the model.
     *
     * This method should return the searcher (search engine) that will handle indexing and searching of this model's data.
     *
     * @return SearcherEngines The search engine being used for the model.
     */
    public function searchableUsing(): SearcherEngines;

    /**
     * Retrieve the key used to index the model.
     *
     * This value is typically the unique key or identifier (such as ID or SKU) of the model that allows it to be uniquely indexed in the search system.
     *
     * @return mixed The searchable key (ID, SKU, etc.) used for indexing.
     */
    public function getSearchableKey();

    /**
     * Retrieve the name of the key used to index the model.
     *
     * This is the name of the database column or attribute that uniquely identifies the model in the search index.
     * This is typically used for lookup or for defining the primary key in a search index.
     *
     * @return string The name of the searchable key column.
     */
    public function getSearchableKeyName(): string;

    /**
     * Retrieve the type of the searchable key (usually auto-incrementing).
     *
     * This method should return the data type of the key, which can be helpful for query construction or determining how the key is handled in the database.
     *
     * @return string The type of the searchable key (e.g., "int", "string").
     */
    public function getSearchableKeyType(): string;

    /**
     * Retrieve the attributes that should be indexed for the model.
     *
     * This method allows the model to specify which attributes should be included in the search index.
     * It's useful for models with a large number of attributes where you may want to control which fields are searchable.
     *
     * @return array<string> List of attribute names to be indexed.
     */
    public function getSearchableAttributes(): array;
}
