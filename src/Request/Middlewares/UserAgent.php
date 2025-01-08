<?php

declare(strict_types=1);

namespace Maginium\Framework\Request\Middlewares;

use Jenssegers\Agent\Agent;
use Maginium\Foundation\Abstracts\Middleware\AbstractHeaderMiddleware;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Request;
use Maginium\Framework\Support\Validator;

/**
 * Class UserAgent.
 *
 * Middleware for appending the current User-Agent to REST API requests.
 */
class UserAgent extends AbstractHeaderMiddleware
{
    /**
     * Header name for the X-User-Agent header.
     *
     * @var string
     */
    private const HEADER_NAME = 'User-Agent';

    /**
     * Header name for the X-User-Agent header.
     *
     * @var string
     */
    private const X_USER_AGENT_HEADER = 'x-user-agent';

    /**
     * UserAgent constructor.
     *
     * Initializes the middleware and sets the logger class name.
     */
    public function __construct()
    {
        // Set Logger class name for logging purposes
        Log::setClassName(static::class);
    }

    /**
     * Retrieves the name of the header to be added.
     *
     * @return string|null The header name.
     */
    protected function getName(): string
    {
        return self::HEADER_NAME;
    }

    /**
     * Retrieves the value of the header to be added.
     *
     * @return string|null The header value.
     */
    protected function getValue(): ?string
    {
        // Initiate an instance of the Agent class, which helps in parsing the User-Agent string
        $agent = Container::resolve(Agent::class);

        // Retrieve the current User-Agent from the request headers
        // If the User-Agent header is not provided, try to get it from a custom header (X-User-Agent)
        $userAgent = Request::header(self::HEADER_NAME);
        $xUserAgent = Request::header(self::X_USER_AGENT_HEADER);

        // Check if both User-Agent and X-User-Agent headers are empty
        if (Validator::isEmpty($userAgent) && Validator::isEmpty($xUserAgent)) {
            // If both are empty, use the User-Agent string from the agent instance
            $userAgent = $agent->getUserAgent();
        } elseif (Validator::isEmpty($userAgent) && ! Validator::isEmpty($xUserAgent)) {
            // If only the User-Agent header is empty but X-User-Agent is present, use X-User-Agent
            $userAgent = $xUserAgent;
        }

        // Add user agent information to the log context for debugging and tracing purposes. This provides detailed information about the client's platform, browser, device, etc.
        Log::withContext(['technical-metadata' => ['user-agent' => [
            'agent' => $userAgent, // The User-Agent string
            'platform' => $agent->platform(), // The platform (OS) of the client
            'browser' => $agent->browser(), // The browser used by the client
            'device' => $agent->device(), // The device type (e.g., desktop, mobile)
            'device-type' => $agent->deviceType(), // More specific device type (e.g., phone, tablet)
            'is-robot' => $agent->isRobot(), // Boolean indicating if the client is a bot or not
        ]]]);

        // Return the User-Agent string
        return $userAgent;
    }
}
