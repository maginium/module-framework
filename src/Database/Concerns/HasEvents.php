<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Concerns;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Maginium\Framework\Support\Facades\Container;
use RuntimeException;

/**
 * Trait HasEvents.
 *
 * @property mixed $dispatcher
 */
trait HasEvents
{
    /**
     * Register observers with the model.
     *
     * @param  object|array|string  $classes
     *
     * @throws RuntimeException
     *
     * @return void
     */
    public static function observe($classes)
    {
        $instance = Container::make(static::class);

        foreach (Arr::wrap($classes) as $class) {
            $instance->registerObserver($class);
        }
    }

    /**
     * Remove all the event listeners for the model.
     *
     * @return void
     */
    public static function flushEventListeners()
    {
        if (! isset(static::$dispatcher)) {
            return;
        }

        $instance = Container::make(static::class);

        foreach ($instance->getObservableEvents() as $event) {
            static::$dispatcher->forget("eloquent.{$event}: " . static::class);
        }

        foreach (array_values($instance->dispatchesEvents) as $event) {
            static::$dispatcher->forget($event);
        }
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return Dispatcher
     */
    public static function getEventDispatcher()
    {
        return static::$dispatcher;
    }
}
