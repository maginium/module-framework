<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Interfaces;

use Illuminate\Console\Scheduling\EventMutex;
use Maginium\Framework\Console\Scheduling\Event;

interface EventMutexInterface extends EventMutex
{
    /**
     * Attempt to obtain an event mutex for the given event.
     *
     * @param  Event  $event
     *
     * @return bool
     */
    public function create($event): bool;

    /**
     * Determine if an event mutex exists for the given event.
     *
     * @param  Event  $event
     *
     * @return bool
     */
    public function exists($event): bool;

    /**
     * Clear the event mutex for the given event.
     *
     * @param  Event  $event
     *
     * @return void
     */
    public function forget($event): void;
}
