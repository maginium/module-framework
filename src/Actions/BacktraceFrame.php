<?php

declare(strict_types=1);

namespace Maginium\Framework\Actions;

use Maginium\Framework\Support\Arr;

/**
 * Class BacktraceFrame.
 *
 * Represents a single frame in a backtrace. A backtrace is a list of function calls
 * or method calls in the execution stack, and this class helps in extracting, analyzing,
 * and manipulating that data. This is useful for debugging or handling application flow.
 */
class BacktraceFrame
{
    /**
     * @var string|null The class name, if present
     */
    public ?string $class;

    /**
     * @var string|null The function/method name, if present
     */
    public ?string $function;

    /**
     * @var bool Whether the method is static or not
     */
    public bool $isStatic;

    /**
     * @var mixed The object reference, if present (for instance methods)
     */
    public mixed $object;

    /**
     * BacktraceFrame constructor.
     *
     * Initializes a new backtrace frame using the provided array of frame data.
     * The array should include data such as class name, function name, object, and type.
     * The constructor extracts relevant information and sets it to the corresponding properties.
     *
     * @param array $frame The frame data to initialize this object.
     */
    public function __construct(array $frame)
    {
        // Get the class name
        $this->class = Arr::get($frame, 'class');

        // Get the object reference (for instance methods)
        $this->object = Arr::get($frame, 'object');

        // Get the function/method name
        $this->function = Arr::get($frame, 'function');

        // Determine if the method is static based on the 'type'
        $this->isStatic = Arr::get($frame, 'type') === '::';
    }

    /**
     * Checks if the frame contains a class.
     *
     * @return bool Returns true if the frame contains a class, otherwise false.
     */
    public function fromClass(): bool
    {
        // Check if the class property is set
        return $this->class !== null;
    }

    /**
     * Checks if the class in the frame is an instance of the given class or subclass.
     *
     * @param string $superClass The class name to check against.
     *
     * @return bool Returns true if the class is either the specified class or a subclass of it.
     */
    public function instanceOf(string $superClass): bool
    {
        // If no class is present in the frame, return false
        if (! $this->fromClass()) {
            return false;
        }

        // Check if the class matches or is a subclass of the given class
        return $this->class === $superClass
        || is_subclass_of($this->class, $superClass); // Checks inheritance hierarchy
    }

    /**
     * Matches the current frame's class, function, and static status with the provided values.
     *
     * @param string      $class     The class name to match.
     * @param string      $method    The method name to match.
     * @param bool|null   $isStatic  Optionally, the static status to match.
     *
     * @return bool Returns true if all conditions (class, method, static) match.
     */
    public function matches(string $class, string $method, ?bool $isStatic = null): bool
    {
        // If $isStatic is null, ignore it, otherwise check if the static status matches
        $matchesStatic = $isStatic === null || $this->isStatic === $isStatic;

        // Check if the class, method, and static status all match the frame
        return $this->instanceOf($class)
            && $this->function === $method
            && $matchesStatic;
    }

    /**
     * Retrieves the object associated with the frame (if any).
     *
     * @return mixed The object associated with the frame, or null if not present.
     */
    public function getObject()
    {
        // Return the object property
        return $this->object;
    }
}
