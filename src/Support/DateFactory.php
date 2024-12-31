<?php

declare(strict_types=1);

namespace Maginium\Framework\Support;

use Carbon\Factory;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Foundation\Exceptions\RuntimeException;

/**
 * @see https://carbon.nesbot.com/docs/
 * @see https://github.com/briannesbitt/Carbon/blob/master/src/Carbon/Factory.php
 *
 * @method \Maginium\Framework\Support\Carbon create($year = 0, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0, $tz = null)
 * @method \Maginium\Framework\Support\Carbon createFromDate($year = null, $month = null, $day = null, $tz = null)
 * @method \Maginium\Framework\Support\Carbon|false createFromFormat($format, $time, $tz = null)
 * @method \Maginium\Framework\Support\Carbon createFromTime($hour = 0, $minute = 0, $second = 0, $tz = null)
 * @method \Maginium\Framework\Support\Carbon createFromTimeString($time, $tz = null)
 * @method \Maginium\Framework\Support\Carbon createFromTimestamp($timestamp, $tz = null)
 * @method \Maginium\Framework\Support\Carbon createFromTimestampMs($timestamp, $tz = null)
 * @method \Maginium\Framework\Support\Carbon createFromTimestampUTC($timestamp)
 * @method \Maginium\Framework\Support\Carbon createMidnightDate($year = null, $month = null, $day = null, $tz = null)
 * @method \Maginium\Framework\Support\Carbon|false createSafe($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $tz = null)
 * @method void disableHumanDiffOption($humanDiffOption)
 * @method void enableHumanDiffOption($humanDiffOption)
 * @method mixed executeWithLocale($locale, $func)
 * @method \Maginium\Framework\Support\Carbon fromSerialized($value)
 * @method array getAvailableLocales()
 * @method array getDays()
 * @method int getHumanDiffOptions()
 * @method array getIsoUnits()
 * @method array getLastErrors()
 * @method string getLocale()
 * @method int getMidDayAt()
 * @method \Maginium\Framework\Support\Carbon|null getTestNow()
 * @method \Symfony\Component\Translation\TranslatorInterface getTranslator()
 * @method int getWeekEndsAt()
 * @method int getWeekStartsAt()
 * @method array getWeekendDays()
 * @method bool hasFormat($date, $format)
 * @method bool hasMacro($name)
 * @method bool hasRelativeKeywords($time)
 * @method bool hasTestNow()
 * @method \Maginium\Framework\Support\Carbon instance($date)
 * @method bool isImmutable()
 * @method bool isModifiableUnit($unit)
 * @method bool isMutable()
 * @method bool isStrictModeEnabled()
 * @method bool localeHasDiffOneDayWords($locale)
 * @method bool localeHasDiffSyntax($locale)
 * @method bool localeHasDiffTwoDayWords($locale)
 * @method bool localeHasPeriodSyntax($locale)
 * @method bool localeHasShortUnits($locale)
 * @method void macro($name, $macro)
 * @method \Maginium\Framework\Support\Carbon|null make($var)
 * @method \Maginium\Framework\Support\Carbon maxValue()
 * @method \Maginium\Framework\Support\Carbon minValue()
 * @method void mixin($mixin)
 * @method \Maginium\Framework\Support\Carbon now($tz = null)
 * @method \Maginium\Framework\Support\Carbon parse($time = null, $tz = null)
 * @method string pluralUnit(string $unit)
 * @method void resetMonthsOverflow()
 * @method void resetToStringFormat()
 * @method void resetYearsOverflow()
 * @method void serializeUsing($callback)
 * @method void setHumanDiffOptions($humanDiffOptions)
 * @method bool setLocale($locale)
 * @method void setMidDayAt($hour)
 * @method void setTestNow($testNow = null)
 * @method void setToStringFormat($format)
 * @method void setTranslator(\Symfony\Component\Translation\TranslatorInterface $translator)
 * @method void setUtf8($utf8)
 * @method void setWeekEndsAt($day)
 * @method void setWeekStartsAt($day)
 * @method void setWeekendDays($days)
 * @method bool shouldOverflowMonths()
 * @method bool shouldOverflowYears()
 * @method string singularUnit(string $unit)
 * @method \Maginium\Framework\Support\Carbon today($tz = null)
 * @method \Maginium\Framework\Support\Carbon tomorrow($tz = null)
 * @method void useMonthsOverflow($monthsOverflow = true)
 * @method void useStrictMode($strictModeEnabled = true)
 * @method void useYearsOverflow($yearsOverflow = true)
 * @method \Maginium\Framework\Support\Carbon yesterday($tz = null)
 */
class DateFactory
{
    /**
     * The type (class) of dates that should be created.
     *
     * @var string
     */
    protected static $dateClass;

    /**
     * This callable may be used to intercept date creation.
     *
     * @var callable
     */
    protected static $callable;

    /**
     * The Carbon factory that should be used when creating dates.
     *
     * @var object
     */
    protected static $factory;

    /**
     * The default class that will be used for all created dates.
     *
     * @var string
     */
    public const DEFAULT_CLASS_NAME = Carbon::class;

    /**
     * Use the given handler when generating dates (class name, callable, or factory).
     *
     * This method allows you to specify how Carbon will create date instances, whether
     * through a class, callable, or a custom factory. It validates the type of handler
     * provided and delegates it to the appropriate method.
     *
     * @param  mixed  $handler The date creation handler, which can be a class name, callable, or a factory object.
     *
     * @throws InvalidArgumentException If the handler is not valid.
     *
     * @return mixed The result of the chosen handler method.
     */
    public static function use($handler)
    {
        // If the handler is an object and callable, use the callable handler.
        if (is_callable($handler) && is_object($handler)) {
            return static::useCallable($handler);
        }

        // If the handler is a string (assumed to be a class name), use the class handler.
        if (Validator::isString($handler)) {
            return static::useClass($handler);
        }

        // If the handler is an instance of a Factory, use the factory handler.
        if ($handler instanceof Factory) {
            return static::useFactory($handler);
        }

        // If none of the conditions match, throw an exception indicating an invalid handler.
        throw InvalidArgumentException::make('Invalid date creation handler. Please provide a class name, callable, or Carbon factory.');
    }

    /**
     * Reset to the default date generation class.
     *
     * This method resets any custom handler (callable, class, or factory) and returns
     * to the default date creation behavior of Carbon.
     *
     * @return void
     */
    public static function useDefault(): void
    {
        static::$dateClass = null;   // Reset the date class.
        static::$callable = null;    // Reset any callable handler.
        static::$factory = null;     // Reset any factory handler.
    }

    /**
     * Set a callable to be executed for every date creation.
     *
     * This allows a user to specify a callable that will handle date creation.
     * The callable will override other date creation methods (class or factory).
     *
     * @param  callable  $callable A callable to be used for date generation.
     *
     * @return void
     */
    public static function useCallable(callable $callable): void
    {
        static::$callable = $callable;  // Set the callable handler.

        // Reset the other handlers (class and factory) to avoid conflicts.
        static::$dateClass = null;
        static::$factory = null;
    }

    /**
     * Use the specified class for generating dates.
     *
     * This method allows you to specify a custom date class that should be used
     * for generating dates instead of Carbon.
     *
     * @param  string  $dateClass The fully qualified class name to be used for date creation.
     *
     * @return void
     */
    public static function useClass($dateClass): void
    {
        static::$dateClass = $dateClass;  // Set the custom date class.

        // Reset other handlers (callable and factory) to avoid conflicts.
        static::$factory = null;
        static::$callable = null;
    }

    /**
     * Use a custom factory for generating dates.
     *
     * This method allows you to specify a factory object that handles date creation.
     * The factory can be used for more complex date generation logic.
     *
     * @param  object  $factory The factory object to be used for date creation.
     *
     * @return void
     */
    public static function useFactory($factory): void
    {
        static::$factory = $factory;  // Set the factory handler.

        // Reset other handlers (callable and class) to avoid conflicts.
        static::$dateClass = null;
        static::$callable = null;
    }

    /**
     * Handle dynamic calls for date generation.
     *
     * This method intercepts calls to undefined methods and dynamically handles date generation.
     * Depending on the configuration (callable, factory, or class), it routes the call appropriately.
     * It supports method chaining and macros as well.
     *
     * @param  string  $method The method being called dynamically.
     * @param  array  $parameters The parameters to pass to the dynamic method.
     *
     * @throws RuntimeException If the method cannot be resolved.
     *
     * @return mixed The generated date or the result of the dynamic method call.
     */
    public function __call($method, $parameters)
    {
        $defaultClassName = static::DEFAULT_CLASS_NAME;  // Get the default class for date creation.

        // If a callable handler is defined, execute the callable with the generated date.
        if (static::$callable) {
            return call_user_func(static::$callable, $defaultClassName::$method(...$parameters));
        }

        // If a factory handler is defined, delegate the method call to the factory.
        if (static::$factory) {
            return static::$factory->{$method}(...$parameters);
        }

        $dateClass = static::$dateClass ?: $defaultClassName;  // Use the custom class if defined, otherwise default.

        // Check if the class has the method or a macro method.
        if (Reflection::methodExists($dateClass, $method) ||
            (Reflection::methodExists($dateClass, 'hasMacro') && $dateClass::hasMacro($method))) {
            return $dateClass::$method(...$parameters);
        }

        // If method is not found, create the date with the default class.
        $date = $defaultClassName::$method(...$parameters);

        // If the custom class has an instance method, pass the date object to it.
        if (Reflection::methodExists($dateClass, 'instance')) {
            return $dateClass::instance($date);
        }

        // If no instance method is found, assume the custom class has a DateTime compatible constructor.
        return new $dateClass($date->format('Y-m-d H:i:s.u'), $date->getTimezone());
    }
}
