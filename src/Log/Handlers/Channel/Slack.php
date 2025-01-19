<?php

declare(strict_types=1);

namespace Maginium\Framework\Log\Handlers\Channel;

use Maginium\Framework\Config\Enums\ConfigDrivers;
use Maginium\Framework\Log\Enums\LogLevel;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\AppState;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Facades\Request;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\SlackWebhookHandler;

/**
 * Class Slack.
 *
 * This class represents a handler for logging messages to Slack. It extends the SlackWebhookHandler from Monolog
 * and is responsible for sending formatted log messages to a configured Slack channel. The configuration can be
 * customized to define the webhook URL, channel, message format, and other settings.
 */
class Slack extends SlackWebhookHandler
{
    /**
     * The log level for this handler, corresponding to one of the Monolog\Logger constants (e.g., DEBUG, INFO, CRITICAL).
     * Subclasses must define this property to specify the log level.
     */
    protected int $type = LogLevel::ALERT;

    /**
     * Slack constructor.
     *
     * Initializes the Slack log handler using configuration values retrieved from the Config class.
     * These values determine how the log messages are formatted and where they are sent.
     */
    public function __construct()
    {
        // Retrieve the Slack Webhook URL from configuration.
        $webhookUrl = Config::driver(ConfigDrivers::ENV)->getString('slack.webhook_url');

        // Retrieve the Slack channel where messages will be sent. Defaults to null if not configured.
        $channel = Config::driver(ConfigDrivers::ENV)->getString('slack.channel', null);

        // Retrieve the application name to be used as the default Slack username.
        $appName = Config::driver(ConfigDrivers::ENV)->getString('APP_NAME');

        // Retrieve the Slack username to use for the bot. Defaults to the app name if not configured.
        $username = Config::driver(ConfigDrivers::ENV)->getString('slack.username', $appName);

        // Retrieve the flag to determine whether to use attachments in the Slack message. Defaults to true.
        $useAttachment = Config::driver(ConfigDrivers::ENV)->getBool('slack.use_attachment', true);

        // Retrieve the emoji to be used as the bot's icon in Slack. Defaults to null if not configured.
        $iconEmoji = Config::driver(ConfigDrivers::ENV)->getString('slack.icon_emoji', null);

        // Retrieve the flag that controls whether to include additional context and extra data in the attachment. Defaults to true.
        $useShortAttachment = Config::driver(ConfigDrivers::ENV)->getBool('slack.use_short_attachment', true);

        // Retrieve the flag to determine whether additional context and extra data should be included in the Slack message. Defaults to true.
        $includeContextAndExtra = Config::driver(ConfigDrivers::ENV)->getBool('slack.include_context_and_extra', true);

        // Retrieve the log level to be used for sending messages to Slack. This determines the severity of logs that are sent.
        $level = Config::driver(ConfigDrivers::ENV)->getInt('slack.level', $this->type);

        // Retrieve an array of fields to be excluded from the Slack message.
        $excludeFields = Config::driver(ConfigDrivers::ENV)->getArray('slack.exclude_fields', []);

        // Retrieve the boolean flag to determine whether log messages should bubble up to other handlers. Defaults to true.
        $bubble = Config::driver(ConfigDrivers::ENV)->getBool('slack.bubble', true);

        // Call the parent constructor to initialize the SlackWebhookHandler with the configuration values.
        // This sets up the handler with the necessary settings for sending messages to Slack.
        parent::__construct(
            channel: $channel, // Slack Channel
            level: $level, // Log level (e.g., ALERT)
            iconEmoji: $iconEmoji, // Bot's Icon Emoji
            webhookUrl: $webhookUrl, // Slack Webhook URL
            username: $username, // Slack Username (Bot Name)
            useAttachment: $useAttachment, // Whether to use Slack Attachments
            bubble: $bubble, // Whether messages should bubble to other handlers
            excludeFields: $excludeFields, // List of fields to exclude from the message
            useShortAttachment: $useShortAttachment, // Use short attachment format for extra data
            includeContextAndExtra: $includeContextAndExtra, // Include context and extra in the attachment
        );
    }

    /**
     * Set the formatter for this handler.
     *
     * This method ensures that the formatter includes stack traces in the formatted log message.
     * It passes the configured formatter to the parent class.
     *
     * @param  FormatterInterface  $formatter  The formatter to be applied to the log records.
     *
     * @return HandlerInterface The handler instance, for chaining.
     */
    public function setFormatter(FormatterInterface $formatter): HandlerInterface
    {
        /** @var JsonFormatter $formatter */
        return parent::setFormatter(
            $formatter->includeStacktraces(true), // Include stack traces in the formatted output
        );
    }

    /**
     * Get the context information to be included in the log record.
     *
     * This method adds useful context information like client IP, HTTP method, request URI, etc.
     * The context data is added to each log record before sending it to Slack.
     *
     * @return array The context data to be included in the log.
     */
    public function getContext(): array
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
     * Write the log record to the Slack webhook.
     *
     * This method processes the log record, adds extra context, and writes it to Slack.
     * The extra and context fields are formatted before being sent to Slack.
     *
     * {@inheritdoc}
     *
     * @param  array  $record  The log record to be written.
     */
    protected function write(array $record): void
    {
        $this->setLevel($record['level']);

        // Clean up extra data before sending it to Slack
        $record['extra'] = [];

        // Add context data to the record
        $record['context'] = $this->getContext();

        // Send the processed record to Slack
        parent::write($record);
    }
}
