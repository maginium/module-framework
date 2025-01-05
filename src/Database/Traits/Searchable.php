<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Traits;

use Maginium\Foundation\Enums\DataType;
use Maginium\Framework\Database\Enums\Searcher;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;

/**
 * Trait for making models searchable.
 *
 * This trait provides methods to manage searchable attributes and configurations
 * for the models utilizing it. It enables models to define how they should be indexed
 * and queried using a search engine, as well as how to extract data from the model for indexing.
 *
 * @property string $table The table associated with the model.
 * @property string|null $searchableAs Custom field name used for searching.
 * @property string|null $searchableIdentifier Custom field identifier for searching.
 *
 * @method string|null searchableIdentifier() Retrieve the index name for the model.
 * @method string searchableAs() Retrieve the searchable identifier for the model.
 * @method string indexableAs() Get the index name for the model when indexing.
 * @method array toSearchableArray() Get the indexable data array for the model.
 * @method Searcher searchableUsing() Retrieve the search engine used by the model.
 * @method mixed getSearchableKey() Get the value used to index the model.
 * @method string getSearchableKeyName() Retrieve the key name used to index the model.
 * @method string getSearchableKeyType() Get the type of the auto-incrementing key for querying models.
 * @method array getSearchableAttributes() Retrieve the attributes that should be indexed for the model.
 */
trait Searchable
{
    /**
     * Constant for the default prefix for elastic index.
     */
    public const DEFAULT_INDEX_PREFIX = 'elastic_indexer_';

    /**
     * Constant for the default key type when not determined.
     */
    public const DEFAULT_KEY_TYPE = DataType::INT;

    /**
     * Retrieve the index name for the model.
     *
     * This method returns the identifier used to index the model in the search engine.
     * It first checks for a custom identifier; if none exists, it attempts to generate one
     * by pluralizing the table name.
     *
     * @return string|null The searchable identifier, or null if not set.
     */
    public function searchableIdentifier(): ?string
    {
        // Check for a custom searchable identifier; if not found, use the pluralized table name
        return static::$searchableIdentifier ?? Str::plural(static::$table) ?? null;
    }

    /**
     * Retrieve the searchable identifier for the model.
     *
     * This method returns the field used to define the searchable identifier in the search engine.
     * It can be overridden to specify a custom search identifier, with a default fallback to a
     * name based on the model's table name.
     *
     * @return string The searchable name for the model.
     */
    public function searchableAs(): string
    {
        return Str::snake(Str::pluralStudly(Reflection::getShortName($this)));
    }

    /**
     * Get the index name for the model when indexing.
     *
     * @return string
     */
    public function indexableAs(): string
    {
        // Check for a custom field name used for indexing, otherwise use a default pattern
        $searchableAs = $this->searchableAs() ?? self::DEFAULT_INDEX_PREFIX . Str::lower($this->searchableIdentifier());

        // Check for a custom field name used for searching, otherwise use a default pattern
        return Str::isPlural($searchableAs) ? $searchableAs : Str::plural($searchableAs);
    }

    /**
     * Get the indexable data array for the model.
     *
     * This method retrieves the data array to be indexed by the search engine.
     * It will check if a custom `toDataArray()` method exists, falling back to the
     * standard `toArray()` method if not.
     *
     * @return array<string> The data array for indexing.
     */
    public function toSearchableArray(): array
    {
        // If the model has a custom `toDataArray` method, use it; otherwise, fallback to `toArray`
        return Php::isCallable([$this, 'toDataArray']) ? $this->toDataArray() : $this->toArray();
    }

    /**
     * Retrieve the search engine used by the model.
     *
     * This method specifies which search engine the model should use. In this case,
     * it defaults to ElasticSearch. You can extend this method if you wish to support
     * other search engines in the future.
     *
     * @return Searcher The search engine constant.
     */
    public function searchableUsing(): Searcher
    {
        // Return the default search engine (ElasticSearch)
        return Searcher::ELASTIC_SEARCH();
    }

    /**
     * Get the value used to index the model.
     *
     * This method retrieves the primary key value of the model, which is used to index
     * the model in the search engine. The method assumes the model has a `getId()` method
     * to retrieve the primary key.
     *
     * @return mixed The primary key value of the model.
     */
    public function getSearchableKey()
    {
        // Assuming 'getId' is the primary key getter method
        return $this->getId();
    }

    /**
     * Retrieve the key name used to index the model.
     *
     * This method retrieves the field name that is used to store the primary key value.
     * This is typically the same name as the model's primary key field.
     *
     * @return string The name of the primary key field.
     */
    public function getSearchableKeyName(): string
    {
        // Retrieve the name of the primary key field
        return $this->getKeyName();
    }

    /**
     * Get the type of the auto-incrementing key for querying models.
     *
     * This method checks the type of the searchable key (typically an integer or string).
     * It attempts to call the `getSearchableKey` method to determine the key type and
     * returns either 'int', 'string', or a default type of 'int' if not determinable.
     *
     * @return string The type of the searchable key, typically 'int' or 'string'.
     */
    public function getSearchableKeyType(): string
    {
        // Check if the `getSearchableKey` method is callable to retrieve the key value
        if (Php::isCallable([$this, 'getSearchableKey'])) {
            $key = $this->getSearchableKey();
            $keyType = gettype($key);

            // Return the key type if it's either integer or string, otherwise default to 'int'
            return $keyType === DataType::INTEGER || $keyType === DataType::STRING ? $keyType : self::DEFAULT_KEY_TYPE;
        }

        // Fallback to default 'int' if the key method is not callable
        return DataType::INT;
    }

    /**
     * Retrieve the attributes that should be indexed for the model.
     *
     * This method allows the model to specify which attributes should be included in the search index.
     * If no specific attributes are defined, it will return an empty array by default.
     *
     * @return array<string> List of attribute names to be indexed.
     */
    public function getSearchableAttributes(): array
    {
        // Return an empty array by default; extend this method to specify searchable attributes
        return [];
    }
}
