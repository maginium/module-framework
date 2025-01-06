<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder as ESClientBuilder;
use Elasticsearch\ConnectionPool\Selectors\StickyRoundRobinSelector;
use Elasticsearch\Handlers\CurlHandler;
use Maginium\Framework\Elasticsearch\Helpers\Data as ElasticsearchHelper;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Validator;
use Psr\Log\LoggerInterface;

/**
 * ElasticSearch client builder service.
 */
class ClientBuilder
{
    /**
     * @var ESClientBuilder
     */
    private $clientBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array Default options for client configuration.
     */
    private $defaultOptions = [
        'http_auth_pwd' => null,
        'http_auth_user' => null,
        'enable_http_auth' => false,
        'servers' => 'localhost:9200',
        'http_auth_encoded' => false,
        'is_debug_mode_enabled' => false,
        'max_parallel_handles' => 100, // Default Elasticsearch parallel handles.
    ];

    /**
     * @var string Selector class for connection pool.
     */
    private $selector;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger Logger for debugging.
     * @param ESClientBuilder $clientBuilder Elasticsearch client builder.
     * @param string $selector Node selector class (default: StickyRoundRobinSelector).
     */
    public function __construct(
        LoggerInterface $logger,
        ESClientBuilder $clientBuilder,
        string $selector = StickyRoundRobinSelector::class,
    ) {
        $this->logger = $logger;
        $this->selector = $selector;
        $this->clientBuilder = $clientBuilder;
    }

    /**
     * Build the Elasticsearch client with the provided options.
     *
     * @param array $options Custom client options. Merged with default options.
     *
     * @return Client Elasticsearch client.
     */
    public function build(array $options = []): Client
    {
        // If options are empty, fallback to default configuration.
        $options = Validator::isEmpty($options) ? ElasticsearchHelper::getConfig() : $options;

        // Merge user-provided options with default settings.
        $options = Arr::merge($this->defaultOptions, $options);

        // Create a new client builder instance.
        $clientBuilder = $this->clientBuilder->create();

        // Configure hosts.
        $hosts = $this->getHosts($options);

        if (! Validator::isEmpty($hosts)) {
            $clientBuilder->setHosts($hosts);
        }

        // Configure debugging if enabled.
        if (! Validator::isEmpty($options['is_debug_mode_enabled'])) {
            $this->configureDebugging($clientBuilder);
        }

        // Configure max parallel handles.
        if (! Validator::isEmpty($options['max_parallel_handles'])) {
            $this->configureHandler($clientBuilder, $options['max_parallel_handles']);
        }

        // Configure HTTP authentication if enabled.
        if (! Validator::isEmpty($options['enable_http_auth']) && $this->isHttpAuthValid($options)) {
            $this->configureHttpAuth($clientBuilder, $options);
        }

        // Set the node selector if applicable.
        $this->configureSelector($clientBuilder, $hosts);

        // Build and return the Elasticsearch client.
        return $clientBuilder->build();
    }

    /**
     * Configure debugging for the Elasticsearch client.
     *
     * Sets up logging and tracing to assist in debugging issues by outputting detailed request/response information.
     *
     * @param ESClientBuilder $clientBuilder The client builder instance to configure.
     *
     * @return void
     */
    private function configureDebugging(ESClientBuilder $clientBuilder): void
    {
        // Set a logger to capture logs for debugging purposes.
        $clientBuilder->setLogger($this->logger);

        // Set a tracer to track detailed HTTP request/response traces.
        $clientBuilder->setTracer($this->logger);
    }

    /**
     * Configure the handler for parallel HTTP requests.
     *
     * @param ESClientBuilder $clientBuilder The client builder instance to configure.
     * @param int $maxHandles The maximum number of parallel HTTP requests that can be handled.
     *
     * @return void
     */
    private function configureHandler(ESClientBuilder $clientBuilder, int $maxHandles): void
    {
        // Define handler parameters for maximum parallel handles.
        $handlerParams = ['max_handles' => $maxHandles];

        // Create a handler using the default builder with the defined parameters.
        $handler = ESClientBuilder::defaultHandler($handlerParams);

        // Set the handler in the client builder.
        $clientBuilder->setHandler($handler);
    }

    /**
     * Configure HTTP authentication for the Elasticsearch client.
     *
     * Adds the required authentication header to the client's connection parameters.
     *
     * @param ESClientBuilder $clientBuilder The client builder instance to configure.
     * @param array $options The configuration options containing authentication details.
     *
     * @return void
     */
    private function configureHttpAuth(ESClientBuilder $clientBuilder, array $options): void
    {
        // Generate the authorization header using the provided credentials.
        $authHeader = $this->getAuthHeader($options);

        // Set the connection parameters with the generated authorization header.
        $clientBuilder->setConnectionParams([
            'client' => ['headers' => ['Authorization' => [$authHeader]]],
        ]);
    }

    /**
     * Validate the presence and format of HTTP authentication credentials.
     *
     * @param array $options The configuration options containing authentication details.
     *
     * @return bool True if the credentials are valid, false otherwise.
     */
    private function isHttpAuthValid(array $options): bool
    {
        // Ensure that the username, password, and encoding flag are all set and not empty.
        return ! Validator::isEmpty($options['http_auth_user']) &&
               ! Validator::isEmpty($options['http_auth_pwd']) &&
               ! Validator::isEmpty($options['http_auth_encoded']);
    }

    /**
     * Configure the node selector for the Elasticsearch client.
     *
     * Determines the strategy for selecting nodes when multiple hosts are specified.
     *
     * @param ESClientBuilder $clientBuilder The client builder instance to configure.
     * @param array $hosts The list of Elasticsearch hosts.
     *
     * @return void
     */
    private function configureSelector(ESClientBuilder $clientBuilder, array $hosts): void
    {
        // Use the default selector unless there is only one host, in which case a sticky selector is preferred.
        $selector = $this->selector;

        if (count($hosts) <= 1) {
            // Sticky selector ensures requests to a single host remain consistent.
            $selector = StickyRoundRobinSelector::class;
        }

        // Set the selector in the client builder.
        $clientBuilder->setSelector($selector);
    }

    /**
     * Generate the HTTP authorization header for Basic Authentication.
     *
     * @param array $options The configuration options containing authentication details.
     *
     * @return string The Base64-encoded authorization header.
     */
    private function getAuthHeader(array $options): string
    {
        // Concatenate the username and password with a colon and encode them in Base64.
        return 'Basic ' . base64_encode($options['http_auth_user'] . ':' . $options['http_auth_pwd']);
    }

    /**
     * Get the handler with max parallel handles configuration.
     *
     * @param int $maxParallelHandles Maximum number of parallel handles.
     *
     * @return CurlHandler
     */
    private function getHandler(int $maxParallelHandles): callable
    {
        return ESClientBuilder::defaultHandler([
            'max_handles' => $maxParallelHandles,
        ]);
    }

    /**
     * Set the node selector based on the number of hosts.
     *
     * @param ESClientBuilder $clientBuilder Elasticsearch client builder.
     * @param array $hosts List of hosts to connect to.
     */
    private function setSelector(ESClientBuilder $clientBuilder, array $hosts): void
    {
        if ($this->selector !== null) {
            // Default selector if only one host is available.
            $selector = Php::count($hosts) <= 1 ? StickyRoundRobinSelector::class : $this->selector;
            $clientBuilder->setSelector($selector);
        }
    }

    /**
     * Get the host configurations for the Elasticsearch cluster.
     *
     * @param array $options Configuration options.
     *
     * @return array List of host configurations.
     */
    private function getHosts(array $options): array
    {
        $hosts = [];

        // Ensure 'servers' is an array.
        $servers = Validator::isString($options['servers']) ? Php::explode(',', $options['servers']) : $options['servers'];

        foreach ($servers as $host) {
            if (! Validator::isEmpty($host)) {
                $hosts[] = $this->parseHost($host, $options);
            }
        }

        return $hosts;
    }

    /**
     * Parse a single host configuration.
     *
     * @param string $host Host configuration in 'hostname:port' format.
     * @param array $options Configuration options.
     *
     * @return array Parsed host configuration.
     */
    private function parseHost(string $host, array $options): array
    {
        // Split the host into hostname and port, with a default port of 9200
        [$hostname, $port] = Arr::pad(Php::explode(':', trim($host), 2), 2, 9200);

        // Determine if HTTPS mode is enabled
        $isHttpsEnabled = ! empty($options['enable_https_mode']);

        // Determine if HTTP authentication is enabled
        $isHttpAuthEnabled = ! empty($options['enable_http_auth']);

        return [
            'host' => $hostname,
            'port' => (int)$port,
            'scheme' => $isHttpsEnabled ? 'https' : ($options['scheme'] ?? 'http'),
            'user' => $isHttpAuthEnabled ? ($options['http_auth_user'] ?? null) : null,
            'pass' => $isHttpAuthEnabled ? ($options['http_auth_pwd'] ?? null) : null,
        ];
    }
}
