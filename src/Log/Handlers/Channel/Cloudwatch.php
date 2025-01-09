<?php

declare(strict_types=1);

namespace Maginium\Framework\Log\Handlers\Channel;

use Aws\CloudWatchLogs\CloudWatchLogsClientFactory as ClientFactory;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Config\Enums\ConfigDrivers;
use Maginium\Framework\Log\Enums\LogLevel;
use Maginium\Framework\Log\Enums\LogLevelString;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\AppState;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Facades\Request;
use Maxbanton\Cwh\Handler\CloudWatch as CloudWatchHandler;
use Pagevamp\Exceptions\IncompleteCloudWatchConfig;

/**
 * Class Cloudwatch.
 *
 * This class represents a handler for logging messages to Cloudwatch. It extends the CloudwatchWebhookHandler from Monolog
 * and is responsible for sending formatted log messages to a configured Cloudwatch channel. The configuration can be
 * customized to define the webhook URL, channel, message format, and other settings.
 */
class Cloudwatch extends CloudWatchHandler
{
    /**
     * @var int
     *
     * The log level for this handler, determining the severity of the log messages that this handler processes.
     * Defaults to LogLevel::ALERT, which represents a high-severity level.
     */
    protected int $type = LogLevel::ALERT;

    /**
     * Cloudwatch constructor.
     *
     * Initializes the CloudWatch log handler using configuration values retrieved from
     * the Config class. These values determine how the log messages are formatted and
     * where they are sent.
     */
    public function __construct(
        ClientFactory $clientFactory,
    ) {
        // Retrieve the CloudWatch log group name from the configuration.
        $groupName = Config::driver(ConfigDrivers::ENV)->getString('cloudwatch.group_name', 'magento-log');

        // Retrieve the CloudWatch stream name from the configuration.
        $streamName = Config::driver(ConfigDrivers::ENV)->getString('cloudwatch.stream_name', 'ec2-instance-1');

        // Retrieve the retention period for CloudWatch logs, defaulting to 14 days if not configured.
        $retention = Config::driver(ConfigDrivers::ENV)->getInt('cloudwatch.retention', 14);

        // Retrieve the batch size for CloudWatch logs, defaulting to 10,000 if not configured.
        $batchSize = Config::driver(ConfigDrivers::ENV)->getInt('cloudwatch.batch_size', 10000);

        // Initialize the CloudWatch client using the credentials.
        // The credentials are fetched dynamically using the getCredentials method.
        $client = $clientFactory->create(['args' => $this->getCredentials()]);

        // Retrieve the log level to be used for sending messages to Cloudwatch. This determines the severity of logs that are sent.
        $levelString = Config::driver(ConfigDrivers::ENV)->getString('cloudwatch.level', LogLevelString::INFO);
        $level = LogLevel::getValue($levelString);

        // Retrieve the boolean flag to determine whether log messages should bubble up to other handlers. Defaults to true.
        $bubble = Config::driver(ConfigDrivers::ENV)->getBool('cloudwatch.bubble', true);

        // Call the parent constructor to initialize the Monolog handler with the specified log level
        // and bubble flag. These control the severity level at which this handler will process logs.
        parent::__construct(
            level: $level, // Log level threshold for this handler.
            bubble: $bubble, // Controls log bubbling behavior.
            client: $client, // CloudWatch client for log transmission.
            group: $groupName, // CloudWatch log group name.
            stream: $streamName,// CloudWatch log stream name.
            retention: $retention, // Retention policy in days for CloudWatch logs.
            batchSize: $batchSize,  // Maximum number of logs sent in one batch.
        );
    }

    /**
     * This is the way config should be defined in config/logging.php
     * in key cloudwatch.
     *
     * @throws IncompleteCloudWatchConfig
     *
     * @return array
     */
    protected function getCredentials()
    {
        // Retrieve the AWS endpoint from the configuration.
        $endpoint = Config::driver(ConfigDrivers::ENV)->getString('aws.endpoint');

        // Retrieve the AWS region from the configuration.
        $region = Config::driver(ConfigDrivers::ENV)->getString('aws.region');

        // Retrieve the AWS API version from the configuration.
        $version = Config::driver(ConfigDrivers::ENV)->getString('aws.version');

        // Retrieve the AWS credentials key, if available, defaulting to null if not configured.
        $key = Config::driver(ConfigDrivers::ENV)->getString('aws.credentials.key', null);

        // Retrieve the AWS credentials secret, if available, defaulting to null if not configured.
        $secret = Config::driver(ConfigDrivers::ENV)->getString('aws.credentials.secret', null);

        // Check if the 'region' key exists in the configuration
        if (! $region) {
            throw new LocalizedException(__('Missing region key-value'));
        }

        // Prepare the AWS credentials array with region and version
        $awsCredentials = [
            'region' => $region,
            'version' => $version,
            'endpoint' => $endpoint,
        ];

        // Add credentials to the array if both key and secret are provided
        if ($key && $secret) {
            $awsCredentials['credentials'] = [
                'key' => $key,
                'secret' => $secret,
            ];
        }

        return $awsCredentials;
    }

    /**
     * Get the context information to be included in the log record.
     *
     * This method adds useful context information like client IP, HTTP method, request URI, etc.
     * The context data is added to each log record before sending it to Cloudwatch.
     *
     * @return array The context data to be included in the log.
     */
    protected function getContext(): array
    {
        return Arr::filter([
            'uri' => Request::getRequestUri(), // Request URI
            'host' => Request::getHttpHost(), // Host of the request
            'client_ip' => Request::getClientIp(), // Client's IP address
            'app_mode' => AppState::getMode() === AppState::MODE_DEVELOPER
                ? 'Developer Mode'
                : (AppState::getMode() === AppState::MODE_DEFAULT
                    ? 'Default Mode'
                    : 'Production Mode'), // Client's IP address
            'command' => Arr::exists($_SERVER, 'argv') ? $_SERVER['argv'] : null, // Command-line arguments as array
            'method' => Arr::exists($_SERVER, 'REQUEST_METHOD') ? $_SERVER['REQUEST_METHOD'] : null, // HTTP request method (GET, POST, etc.)
        ]);
    }

    /**
     * Write the log record to the Cloudwatch webhook.
     *
     * This method processes the log record, adds extra context, and writes it to Cloudwatch.
     * The extra and context fields are formatted before being sent to Cloudwatch.
     *
     * {@inheritdoc}
     *
     * @param  array  $record  The log record to be written.
     */
    protected function write(array $record): void
    {
        // Clean up extra data before sending it to Cloudwatch
        $record['extra'] = [];
        // Add context data to the record
        $record['context'] = $this->getContext();

        // Send the processed record to Cloudwatch
        parent::write($record);
    }
}
