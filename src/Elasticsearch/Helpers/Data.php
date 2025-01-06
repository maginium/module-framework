<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Helpers;

use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Validator;

/**
 * Class Data.
 *
 * Provides methods to retrieve Elasticsearch client configuration from Magento configuration.
 * This class abstracts the retrieval of various Elasticsearch settings from Magento's configuration
 * and allows the easy building of Elasticsearch connection options.
 */
class Data
{
    /**
     * XML path for Elasticsearch server hostname configuration.
     *
     * @var string
     */
    private const XML_PATH_ELASTICSEARCH_SERVER_HOSTNAME = 'catalog/search/opensearch_server_hostname';

    /**
     * XML path for Elasticsearch server timeout configuration.
     *
     * @var string
     */
    private const XML_PATH_ELASTICSEARCH_SERVER_TIMEOUT = 'catalog/search/opensearch_server_timeout';

    /**
     * XML path for Elasticsearch scheme configuration (http or https).
     *
     * @var string
     */
    // Todo: add config system.xml
    private const XML_PATH_ELASTICSEARCH_SCHEME = 'catalog/search/schema';

    /**
     * XML path for Elasticsearch server port configuration.
     *
     * @var string
     */
    private const XML_PATH_ELASTICSEARCH_SERVER_PORT = 'catalog/search/opensearch_server_port';

    /**
     * XML path for Elasticsearch authentication enablement configuration.
     *
     * @var string
     */
    private const XML_PATH_ELASTICSEARCH_ENABLE_AUTH = 'catalog/search/opensearch_auth';

    /**
     * XML path for Elasticsearch username configuration.
     *
     * @var string
     */
    private const XML_PATH_ELASTICSEARCH_USERNAME = 'catalog/search/opensearch_username';

    /**
     * XML path for Elasticsearch password configuration.
     *
     * @var string
     */
    private const XML_PATH_ELASTICSEARCH_PASSWORD = 'catalog/search/opensearch_password';

    /**
     * XML path for maximum parallel handles configuration.
     *
     * @var string
     */
    private const XML_PATH_MAX_PARALLEL_HANDLES = 'catalog/search';

    /**
     * XML path for Elasticsearch index prefix configuration.
     *
     * @var string
     */
    private const XML_PATH_INDICES_PREFIX = 'catalog/search/opensearch_index_prefix';

    /**
     * XML path for Elasticsearch number of replicas configuration.
     *
     * @var string
     */
    private const XML_PATH_NUMBER_OF_REPLICAS = 'catalog/search/number_of_replicas';

    /**
     * XML path for Elasticsearch number of shards configuration.
     *
     * @var string
     */
    private const XML_PATH_NUMBER_OF_SHARDS = 'catalog/search/number_of_shards';

    /**
     * XML path for Elasticsearch indices pattern configuration.
     *
     * @var string
     */
    private const XML_PATH_INDICES_PATTERN = 'catalog/search/indices_pattern';

    /**
     * XML path for Elasticsearch batch indexing size configuration.
     *
     * @var string
     */
    private const XML_PATH_BATCH_INDEX_SIZE = 'catalog/search/batch_indexing_size';

    /**
     * Retrieve Elasticsearch server list from Magento configuration.
     *
     * Retrieves a list of Elasticsearch server hostnames. These are typically configured
     * within Magento's admin settings under the 'catalog/search' section.
     *
     * @return array List of Elasticsearch server hostnames.
     */
    public static function getServerList(): array
    {
        return Config::getArray(self::XML_PATH_ELASTICSEARCH_SERVER_HOSTNAME);
    }

    /**
     * Retrieve the Elasticsearch connection timeout value from configuration.
     *
     * This timeout determines how long to wait for a connection to be established before
     * timing out the request.
     *
     * @return int Connection timeout value in seconds.
     */
    public static function getConnectionTimeout(): int
    {
        return Config::getInt(self::XML_PATH_ELASTICSEARCH_SERVER_TIMEOUT);
    }

    /**
     * Retrieve the Elasticsearch scheme (http/https) from configuration.
     *
     * The scheme will be either 'http' or 'https' depending on the setting in the Magento configuration.
     *
     * @return string 'http' or 'https' scheme.
     */
    public static function getScheme(): string
    {
        return Config::getBool(self::XML_PATH_ELASTICSEARCH_SCHEME) ? 'https' : 'http';
    }

    /**
     * Retrieve the Elasticsearch server port from configuration.
     *
     * This value corresponds to the port number used by the Elasticsearch server.
     *
     * @return int Server port number.
     */
    public static function getPort(): int
    {
        return Config::getInt(self::XML_PATH_ELASTICSEARCH_SERVER_PORT);
    }

    /**
     * Check if HTTP authentication is enabled for Elasticsearch.
     *
     * This checks the configuration to determine whether HTTP authentication is enabled
     * for connecting to Elasticsearch, and ensures that both the username and password are provided.
     *
     * @return bool True if HTTP authentication is enabled and valid credentials are set, false otherwise.
     */
    public static function isHttpAuthEnabled(): bool
    {
        $authEnabled = Config::getBool(self::XML_PATH_ELASTICSEARCH_ENABLE_AUTH);

        // Ensure both username and password are configured before enabling HTTP authentication
        return $authEnabled &&
               ! Validator::isEmpty(static::getHttpAuthUser()) &&
               ! Validator::isEmpty(static::getHttpAuthPassword());
    }

    /**
     * Retrieve the HTTP authentication username from configuration.
     *
     * This is the username required to authenticate the request to Elasticsearch,
     * if HTTP authentication is enabled.
     *
     * @return string HTTP authentication username.
     */
    public static function getHttpAuthUser(): string
    {
        return (string)Config::getString(self::XML_PATH_ELASTICSEARCH_USERNAME);
    }

    /**
     * Retrieve the HTTP authentication password from configuration.
     *
     * This is the password associated with the username used for Elasticsearch authentication.
     *
     * @return string HTTP authentication password.
     */
    public static function getHttpAuthPassword(): string
    {
        return (string)Config::getString(self::XML_PATH_ELASTICSEARCH_PASSWORD);
    }

    /**
     * Retrieve the maximum number of parallel handles allowed for Elasticsearch operations.
     *
     * This value is used to limit the number of parallel operations that can be performed
     * to avoid overloading the Elasticsearch server with too many simultaneous requests.
     *
     * @return int Maximum parallel handles value.
     */
    public static function getMaxParallelHandles(): int
    {
        return Config::getInt(self::XML_PATH_MAX_PARALLEL_HANDLES);
    }

    /**
     * Retrieve the number of replicas configuration for Elasticsearch indices.
     *
     * This value specifies how many copies of each Elasticsearch shard should be maintained
     * to ensure high availability and fault tolerance.
     *
     * @return int Number of replicas.
     */
    public static function getNumberOfReplicas(): int
    {
        return Config::getInt(self::XML_PATH_NUMBER_OF_REPLICAS);
    }

    /**
     * Retrieve the number of shards configuration for Elasticsearch indices.
     *
     * This value defines how many primary shards each Elasticsearch index should have.
     * More shards can improve search performance but increase overhead.
     *
     * @return int Number of shards.
     */
    public static function getNumberOfShards(): int
    {
        return Config::getInt(self::XML_PATH_NUMBER_OF_SHARDS);
    }

    /**
     * Retrieve the prefix for Elasticsearch indices from configuration.
     *
     * This prefix is often used to distinguish indices related to a particular Magento store or environment.
     *
     * @return string Prefix for Elasticsearch indices.
     */
    public static function getIndicesPrefix(): string
    {
        return Config::getString(self::XML_PATH_INDICES_PREFIX);
    }

    /**
     * Retrieve the pattern for Elasticsearch indices from configuration.
     *
     * This pattern is used to define the naming convention for Elasticsearch indices.
     *
     * @return string Pattern for Elasticsearch indices.
     */
    public static function getIndicesPattern(): string
    {
        return Config::getString(self::XML_PATH_INDICES_PATTERN);
    }

    /**
     * Retrieve the batch indexing size configuration for Elasticsearch.
     *
     * This value determines how many documents are processed in each batch when indexing documents
     * into Elasticsearch. Larger batch sizes can increase indexing performance but may consume more memory.
     *
     * @return int Batch indexing size.
     */
    public static function getBatchIndexSize(): int
    {
        return Config::getInt(self::XML_PATH_BATCH_INDEX_SIZE);
    }

    /**
     * Get the options array for configuring the Elasticsearch client.
     *
     * This method compiles the various configuration values into an array of options
     * that can be passed to the Elasticsearch client for making requests.
     *
     * @return array Associative array of Elasticsearch client options.
     */
    public static function getConfig(): array
    {
        // Assemble options for the Elasticsearch client
        $options = [
            'port' => static::getPort(),
            'scheme' => static::getScheme(),
            'servers' => static::getServerList(),
            'http_auth_user' => static::getHttpAuthUser(),
            'http_auth_pwd' => static::getHttpAuthPassword(),
            'enable_http_auth' => static::isHttpAuthEnabled(),
            'max_parallel_handles' => static::getMaxParallelHandles(),
        ];

        // Return the assembled options array
        return $options;
    }
}
