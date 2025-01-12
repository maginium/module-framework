<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Interfaces;

use DateTimeInterface;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Maginium\Framework\Console\Scheduling\Event;

/**
 * Interface ScheduleInterface.
 *
 * This interface defines the contract for a scheduling class, providing methods
 * to manage and execute scheduled tasks, Magento commands, and queued jobs.
 */
interface ScheduleInterface
{
    /**
     * Add a new callback event to the schedule.
     *
     * @param  string|callable  $callback
     * @param  array  $parameters
     *
     * @return CallbackEvent
     */
    public function call($callback, array $parameters = []);

    /**
     * Add a new Magento command event to the schedule.
     *
     * @param  string  $command
     * @param  array  $parameters
     *
     * @return Event
     */
    public function command($command, array $parameters = []);

    /**
     * Add a new job callback event to the schedule.
     *
     * @param  object|string  $job
     * @param  string|null  $queue
     * @param  string|null  $connection
     *
     * @return CallbackEvent
     */
    public function job($job, $queue = null, $connection = null);

    /**
     * Add a new command event to the schedule.
     *
     * @param  string  $command
     * @param  array  $parameters
     *
     * @return Event
     */
    public function exec($command, array $parameters = []);

    /**
     * Compile array input for a command.
     *
     * @param  string|int  $key
     * @param  array  $value
     *
     * @return string
     */
    public function compileArrayInput($key, $value);

    /**
     * Determine if the server is allowed to run this event.
     *
     * @param  Event  $event
     * @param  DateTimeInterface  $time
     *
     * @return bool
     */
    public function serverShouldRun(Event $event, DateTimeInterface $time);

    /**
     * Get all of the events on the schedule that are due.
     *
     * @param  Application  $app
     *
     * @return Collection
     */
    public function dueEvents(Application $app);

    /**
     * Get all of the events on the schedule.
     *
     * @return Event[]
     */
    public function events();

    /**
     * Specify the cache store that should be used to store mutexes.
     *
     * @param  string  $store
     *
     * @return $this
     */
    public function useCache($store);
}
