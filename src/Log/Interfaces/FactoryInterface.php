<?php

declare(strict_types=1);

namespace Maginium\Framework\Log\Interfaces;

use Psr\Log\LoggerInterface;

/**
 * Interface FactoryInterface.
 *
 * Defines the contract for managing logging functionality across various channels.
 * This includes retrieving specific log channels, such as those for Slack, CloudWatch,
 * or the default logging system. The interface allows for flexible logging management
 * across different environments and use cases.
 */
interface FactoryInterface
{
    /**
     * Retrieve a log channel instance by name.
     *
     * This method retrieves a log channel instance based on the provided name.
     * Channels could represent specific log handlers (e.g., Slack, CloudWatch) or
     * the default logging configuration.
     *
     * @param  string|null  $name  Optional name of the log channel to retrieve.
     *
     * @return LoggerInterface The log channel instance corresponding to the provided name or default.
     */
    public function channel(?string $name = null): LoggerInterface;
}
