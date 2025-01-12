<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Traits;

use Cron\CronExpression;
use Cron\CronExpressionFactory;
use DateTimeZone;
use DateTimeZoneFactory;
use DOMDocument;
use DOMDocumentFactory;
use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledBackgroundTaskFinishedFactory;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFailedFactory;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFinishedFactory;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskSkippedFactory;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Events\ScheduledTaskStartingFactory;
use Maginium\Framework\Support\Facades\Container;

/**
 * Trait InjectingFactories.
 *
 * This trait provides methods for injecting various factories into the class.
 * These factories include those for Cron expressions, time zones, background task events,
 * and task events (started, finished, failed, skipped). This ensures that scheduled tasks
 * and associated configurations can be generated and handled properly.
 */
trait InjectingFactories
{
    /**
     * Inject the necessary factories for task scheduling, document handling, and event creation.
     *
     * @param DOMDocumentFactory $domDocumentFactory The factory for creating DOMDocument instances.
     * @param DateTimeZoneFactory $dateTimeZoneFactory The factory used to create timezone objects.
     * @param CronExpressionFactory $cronExpressionFactory The factory used to create Cron expressions.
     * @param ScheduledTaskFailedFactory $scheduledTaskFailedFactory The factory for creating scheduled task failed events.
     * @param ScheduledTaskSkippedFactory $scheduledTaskSkippedFactory The factory for creating scheduled task skipped events.
     * @param ScheduledTaskStartingFactory $scheduledTaskStartingFactory The factory for creating scheduled task starting events.
     * @param ScheduledTaskFinishedFactory $scheduledTaskFinishedFactory The factory for creating scheduled task finished events.
     * @param ScheduledBackgroundTaskFinishedFactory $scheduledBackgroundTaskFinishedFactory The factory for the task finished event.
     */
    public function __construct(
        DOMDocumentFactory $domDocumentFactory,
        DateTimeZoneFactory $dateTimeZoneFactory,
        CronExpressionFactory $cronExpressionFactory,
        ScheduledTaskFailedFactory $scheduledTaskFailedFactory,
        ScheduledTaskSkippedFactory $scheduledTaskSkippedFactory,
        ScheduledTaskStartingFactory $scheduledTaskStartingFactory,
        ScheduledTaskFinishedFactory $scheduledTaskFinishedFactory,
        ScheduledBackgroundTaskFinishedFactory $scheduledBackgroundTaskFinishedFactory,
    ) {
    }

    /**
     * Method to create a Cron expression using the injected factory.
     *
     * @param mixed ...$args Arguments to be passed to the factory's create method.
     *
     * @return CronExpression The parsed Cron expression object.
     */
    public function createCronExpression(...$args): CronExpression
    {
        return Container::make(CronExpressionFactory::class)->create(...$args);
    }

    /**
     * Method to create a DateTimeZone instance.
     *
     * @param mixed ...$args Arguments to be passed to the factory's create method.
     *
     * @return DateTimeZone The DateTimeZone instance.
     */
    public function createDateTimeZone(...$args): DateTimeZone
    {
        return Container::make(DateTimeZoneFactory::class)->create(...$args);
    }

    /**
     * Method to create a scheduled background task finished event.
     *
     * @param mixed ...$args Arguments to be passed to the factory's create method.
     *
     * @return ScheduledBackgroundTaskFinished The event instance.
     */
    public function createScheduledBackgroundTaskFinishedEvent(...$args): ScheduledBackgroundTaskFinished
    {
        return Container::make(ScheduledBackgroundTaskFinishedFactory::class)->create(...$args);
    }

    /**
     * Method to create a DOMDocument instance.
     *
     * @param mixed ...$args Arguments to be passed to the factory's create method.
     *
     * @return DOMDocument The created DOMDocument instance.
     */
    public function createDOMDocument(...$args): DOMDocument
    {
        return Container::make(DOMDocumentFactory::class)->create(...$args);
    }

    /**
     * Method to create a scheduled task skipped event.
     *
     * @param mixed ...$args Arguments to be passed to the factory's create method.
     *
     * @return ScheduledTaskSkipped The event instance.
     */
    public function createScheduledTaskSkippedEvent(...$args): ScheduledTaskSkipped
    {
        return Container::make(ScheduledTaskSkippedFactory::class)->create(...$args);
    }

    /**
     * Method to create a scheduled task starting event.
     *
     * @param mixed ...$args Arguments to be passed to the factory's create method.
     *
     * @return ScheduledTaskStarting The event instance.
     */
    public function createScheduledTaskStartingEvent(...$args): ScheduledTaskStarting
    {
        return Container::make(ScheduledTaskSkippedFactory::class)->create(...$args);
    }

    /**
     * Method to create a scheduled task finished event.
     *
     * @param mixed ...$args Arguments to be passed to the factory's create method.
     *
     * @return ScheduledTaskFinished The event instance.
     */
    public function createScheduledTaskFinishedEvent(...$args): ScheduledTaskFinished
    {
        return Container::make(ScheduledTaskFinishedFactory::class)->create(...$args);
    }

    /**
     * Method to create a scheduled task failed event.
     *
     * @param mixed ...$args Arguments to be passed to the factory's create method.
     *
     * @return ScheduledTaskFailed The event instance.
     */
    public function createScheduledTaskFailedEvent(...$args): ScheduledTaskFailed
    {
        return Container::make(ScheduledTaskFailedFactory::class)->create(...$args);
    }
}
