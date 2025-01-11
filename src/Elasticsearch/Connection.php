<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch;

use Elasticsearch\Client;
use Maginium\Framework\Database\Connections\Connection as BaseConnection;
use Maginium\Framework\Database\Interfaces\BuilderInterface;
use Maginium\Framework\Elasticsearch\DSL\Bridge;
use Maginium\Framework\Elasticsearch\DSL\Results;
use Maginium\Framework\Elasticsearch\Exceptions\LogicException;
use Maginium\Framework\Elasticsearch\Interfaces\ClientInterface;
use Maginium\Framework\Elasticsearch\Query\Builder as QueryBuilder;
use Maginium\Framework\Elasticsearch\Query\Processor;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;

/**
 * Class Connection.
 *
 * This class handles the connection to Elasticsearch, including methods for
 * indexing, querying, and managing data within Elasticsearch.
 *
 * It also provides configuration and utility methods for setting up and maintaining
 * the connection and index settings.
 *
 * @method bool indexModify(array $settings)
 * @method bool indexCreate(array $settings = [])
 * @method array indexSettings(string $index)
 * @method array getIndices(bool $all = false)
 * @method bool indexExists(string $index)
 * @method bool indexDelete()
 * @method array indexMappings(string $index)
 * @method array fieldMapping(string $index, string|array $field, bool $raw)
 * @method Results indicesDsl(string $method, array $params)
 * @method Results reIndex(string $from, string $to)
 * @method bool indexAnalyzerSettings(array $settings)
 * @method Results distinctAggregate(string $function, array $wheres, array $options, array $columns)
 * @method Results aggregate(string $function, array $wheres, array $options, array $columns)
 * @method Results distinct(array $wheres, array $options, array $columns, bool $includeDocCount = false)
 * @method Results find(array $wheres, array $options, array $columns)
 * @method Results save(array $data, string $refresh)
 * @method array insertBulk(array $data, bool $returnData = false, string|null $refresh = false)
 * @method Results multipleAggregate(array $functions, array $wheres, array $options, string $column)
 * @method Results deleteAll(array $wheres, array $options = [])
 * @method Results searchRaw(array $bodyParams, bool $returnRaw = false)
 * @method Results aggregationRaw(array $bodyParams)
 * @method Results search(string $searchParams, array $searchOptions, array $wheres, array $options, array $fields, array $columns)
 * @method array toDsl(array $wheres, array $options, array $columns)
 * @method array toDslForSearch(string $searchParams, array $searchOptions, array $wheres, array $options, array $fields, array $columns)
 * @method string openPit(string $keepAlive = '5m')
 * @method bool closePit(string $id)
 * @method Results pitFind(array $wheres, array $options, array $fields, string $pitId, ?array $after, string $keepAlive)
 * @method Results getId(string $_id, array $columns = [], $softDeleteColumn = null)
 */
class Connection extends BaseConnection
{
    public const VALID_AUTH_TYPES = ['http', 'cloud'];

    /**
     * The Elasticsearch connection handler.
     *
     * @var ClientInterface|null
     */
    protected ?ClientInterface $client;

    /**
     * The index name for the Elasticsearch connection.
     *
     * @var string
     */
    protected string $index = '';

    /**
     * The maximum number of results to return in queries.
     *
     * @var int
     */
    protected int $maxSize = 10;

    /**
     * The prefix to be added to index names.
     *
     * @var string
     */
    protected string $indexPrefix = '';

    /**
     * Flag to indicate if sorting by ID is allowed.
     *
     * @var bool
     */
    protected bool $allowIdSort = false;

    /**
     * The index used for error logging.
     *
     * @var string|null
     */
    protected ?string $errorLoggingIndex = null;

    /**
     * Flag to enable or disable SSL verification.
     *
     * @var bool
     */
    protected bool $sslVerification = true;

    /**
     * The number of retries for Elasticsearch operations.
     *
     * @var int|null
     */
    protected ?int $retires = null; //null will use default

    /**
     * The metadata header for Elasticsearch requests.
     *
     * @var mixed|null
     */
    protected mixed $elasticMetaHeader = null;

    /**
     * The name of the connection.
     *
     * @var string|null
     */
    protected ?string $connectionName;

    /**
     * Flag to bypass map validation during indexing.
     *
     * @var bool
     */
    protected bool $byPassMapValidation = false;

    /**
     * The chunk size used for bulk insert operations.
     *
     * @var int
     */
    protected int $insertChunkSize = 1000;

    /**
     * Query processor instance for post-processing queries.
     *
     * @var Processor
     */
    protected $postProcessor;

    /**
     * Connection constructor.
     *
     * Initializes the Elasticsearch connection, configures settings, and prepares
     * the client for communication with Elasticsearch.
     *
     * @param Processor $postProcessor The post processor instance.
     * @param ClientInterface $client The Elasticsearch client instance.
     * @param array $config The configuration settings for the connection.
     */
    public function __construct(
        ClientInterface $client,
        Processor $postProcessor,
        array $config = [],
    ) {
        // Initialize core properties
        $this->config = $config;
        $this->client = $client;
        $this->postProcessor = $postProcessor;
        $this->connectionName = $config['name'] ?? null;

        // Set connection options
        $this->setOptions();

        // Set default query and schema grammars
        $this->useDefaultSchemaGrammar();
        $this->useDefaultQueryGrammar();
    }

    /**
     * Set additional connection options from the configuration.
     *
     * This method processes the configuration array and sets options like
     * index prefix, SSL verification, retry count, and more.
     */
    public function setOptions(): void
    {
        // Set the index prefix if provided
        $this->indexPrefix = $this->config['index_prefix'] ?? '';

        // Set the flag to allow sorting by ID if specified in options
        if (isset($this->config['options']['allow_id_sort'])) {
            $this->allowIdSort = $this->config['options']['allow_id_sort'];
        }

        // Set SSL verification flag
        if (isset($this->config['options']['ssl_verification'])) {
            $this->sslVerification = $this->config['options']['ssl_verification'];
        }

        // Set retry count if specified
        if (! empty($this->config['options']['retires'])) {
            $this->retires = $this->config['options']['retires'];
        }

        // Set custom metadata header for requests
        if (isset($this->config['options']['meta_header'])) {
            $this->elasticMetaHeader = $this->config['options']['meta_header'];
        }

        // Set error logging index with optional prefix
        if (! empty($this->config['error_log_index'])) {
            $this->errorLoggingIndex = $this->indexPrefix
              ? $this->indexPrefix . '_' . $this->config['error_log_index']
              : $this->config['error_log_index'];
        }

        // Set flag to bypass map validation if configured
        if (! empty($this->config['options']['bypass_map_validation'])) {
            $this->byPassMapValidation = $this->config['options']['bypass_map_validation'];
        }

        // Set the chunk size for bulk inserts
        if (! empty($this->config['options']['insert_chunk_size'])) {
            $this->insertChunkSize = $this->config['options']['insert_chunk_size'];
        }
    }

    /**
     * Get the table instance for the given table name.
     *
     * This method is overridden from the BaseConnection class and returns
     * a query builder instance for interacting with the specified table.
     *
     * @param string $table The table name.
     * @param string|null $as An optional alias for the table.
     *
     * @return QueryBuilder The query builder instance.
     */
    public function table($table, $as = null): mixed
    {
        $query = Container::resolve(QueryBuilder::class);

        return $query->from($table);
    }

    /**
     * Disconnect the current Elasticsearch client.
     *
     * This method clears the client instance and disconnects from Elasticsearch.
     */
    public function disconnect(): void
    {
        $this->client = null;
    }

    /**
     * Get the table prefix (same as index prefix in this case).
     *
     * @return string|null The index prefix, or null if not set.
     */
    public function getTablePrefix(): ?string
    {
        return $this->getIndexPrefix();
    }

    /**
     * Get the index prefix.
     *
     * @return string|null The index prefix, or null if not set.
     */
    public function getIndexPrefix(): ?string
    {
        return $this->indexPrefix;
    }

    /**
     * Retrieve information about the Elasticsearch client.
     *
     * @return array The client's information, such as version and build.
     */
    public function getClientInfo(): array
    {
        return $this->client->info();
    }

    /**
     * Get the post-processor instance.
     * {@inheritdoc}
     */
    public function getPostProcessor(): Processor
    {
        // Returns the current post-processor object
        return $this->postProcessor;
    }

    /**
     * Set a new index prefix.
     */
    public function setIndexPrefix($newPrefix): void
    {
        // Sets the new index prefix
        $this->indexPrefix = $newPrefix;
    }

    /**
     * Get the error logging index.
     */
    public function getErrorLoggingIndex(): ?string
    {
        // Returns the error logging index, if defined
        return $this->errorLoggingIndex;
    }

    /**
     * Get the schema grammar instance.
     * {@inheritdoc}
     */
    public function getSchemaGrammar(): Schema\Grammar
    {
        // Returns a new Schema Grammar instance
        return new Schema\Grammar;
    }

    /**
     * Get the index being used.
     */
    public function getIndex(): string
    {
        // Returns the current index
        return $this->index;
    }

    /**
     * Get the driver name (elasticsearch in this case).
     * {@inheritdoc}
     */
    public function getDriverName(): string
    {
        // Returns the string 'elasticsearch' as the driver name
        return 'elasticsearch';
    }

    /**
     * Get the client instance.
     */
    public function getClient(): ?Client
    {
        // Returns the Elasticsearch client instance
        return $this->client->getClient();
    }

    /**
     * Get the maximum size for operations.
     */
    public function getMaxSize(): int
    {
        // Returns the max size value
        return $this->maxSize;
    }

    /**
     * Override the default schema builder.
     *
     * @return Schema\Builder
     */
    public function getSchemaBuilder(): BuilderInterface
    {
        // Returns a new Schema Builder instance
        return Container::make(Schema\Builder::class);
    }

    /**
     * Get the allowed flag for ID sorting.
     */
    public function getAllowIdSort(): bool
    {
        // Returns whether ID sorting is allowed
        return $this->allowIdSort;
    }

    /**
     * Get the bypass map validation flag.
     */
    public function getBypassMapValidation(): bool
    {
        // Returns the flag indicating whether map validation should be bypassed
        return $this->byPassMapValidation;
    }

    /**
     * Get the chunk size for insert operations.
     */
    public function getInsertChunkSize(): int
    {
        // Returns the insert chunk size
        return $this->insertChunkSize;
    }

    /**
     * Set the index for the connection, applying the prefix if needed.
     */
    public function setIndex(string $index): string
    {
        // Check if the index should have a prefix and if it's missing, add it
        $this->index = $this->indexPrefix && ! str_contains($index, $this->indexPrefix . '_')
            ? $this->indexPrefix . '_' . $index
            : $index;

        // Return the updated index value
        return $this->getIndex();
    }

    /**
     * Set the maximum size for operations.
     */
    public function setMaxSize($value): void
    {
        // Sets the new maximum size value
        $this->maxSize = $value;
    }

    /**
     * Get the default post-processor instance.
     * {@inheritdoc}
     */
    protected function getDefaultPostProcessor(): Processor
    {
        // Returns a new default post-processor
        return new Processor;
    }

    /**
     * Get the default query grammar.
     * {@inheritdoc}
     */
    protected function getDefaultQueryGrammar(): Query\Grammar
    {
        // Returns a new default query grammar
        return new Query\Grammar;
    }

    /**
     * Get the default schema grammar.
     * {@inheritdoc}
     */
    protected function getDefaultSchemaGrammar(): Schema\Grammar
    {
        // Returns a new default schema grammar
        return new Schema\Grammar;
    }

    /**
     * Magic method for handling dynamic method calls on the bridge instance.
     */
    public function __call($method, $parameters)
    {
        // Ensure an index is set, or set it to the default index with the prefix
        if (! $this->index) {
            $this->index = $this->indexPrefix . '*';
        }

        // Create a new Bridge instance to handle the method call
        $bridge = Container::get(Bridge::class);

        // Format the method name to match the convention used in Bridge
        $methodName = 'process' . Str::studly($method);

        // Ensure the method exists on the bridge and call it
        if (! Reflection::methodExists($bridge, method: $methodName)) {
            throw new LogicException("{$methodName} does not exist on the bridge.");
        }

        // Call the method and pass the parameters
        return $bridge->{$methodName}(...$parameters);
    }
}
