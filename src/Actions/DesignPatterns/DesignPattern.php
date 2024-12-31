<?php

declare(strict_types=1);

namespace Maginium\Framework\Actions\DesignPatterns;

use Maginium\Framework\Actions\BacktraceFrame;

/**
 * Abstract class DesignPattern.
 *
 * This abstract class serves as a blueprint for implementing design patterns
 * that decorate or modify actions in the application. The design pattern classes
 * that extend this base class are expected to implement the methods for
 * retrieving a trait, recognizing frames (contextual information), and decorating
 * an action instance. This class provides a standard interface for various
 * design patterns that modify or extend the behavior of actions.
 */
abstract class DesignPattern
{
    /**
     * Get the trait to be applied to the action.
     *
     * This abstract method must be implemented by subclasses to return the
     * fully qualified class name of the trait that will be applied to the
     * action. The trait is used to modify or extend the behavior of the action.
     *
     * @return string The fully qualified class name of the trait.
     */
    abstract public function getTrait(): string;

    /**
     * Recognize the frame and determine if it matches a specific context.
     *
     * This abstract method must be implemented by subclasses to determine
     * whether a given backtrace frame matches a specific context or condition.
     * The context can be used to recognize when an action is being executed
     * or when a certain operation is taking place.
     *
     * @param BacktraceFrame $frame The backtrace frame to check.
     *
     * @return bool Returns `true` if the frame matches the expected context,
     *              `false` otherwise.
     */
    abstract public function recognizeFrame(BacktraceFrame $frame): bool;

    /**
     * Decorate the action instance based on the design pattern.
     *
     * This abstract method must be implemented by subclasses to decorate
     * the given action instance. Decoration typically involves enhancing or
     * modifying the behavior of the action, such as applying traits, setting
     * properties, or calling specific methods.
     *
     * @param mixed $instance The action instance to decorate.
     * @param BacktraceFrame $frame The backtrace frame associated with the action.
     *
     * @return mixed The decorated action instance, which may be modified or
     *               wrapped in a different class.
     */
    abstract public function decorate($instance, BacktraceFrame $frame);
}
