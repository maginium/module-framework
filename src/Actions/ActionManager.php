<?php

declare(strict_types=1);

namespace Maginium\Framework\Actions;

use Maginium\Framework\Actions\DesignPatterns\DesignPattern;
use Maginium\Framework\Support\Arr;

/**
 * Class ActionManager.
 *
 * Manages the design patterns, backtrace limits, and command registration.
 * It identifies and decorates actions based on the provided design patterns and manages actions using different traits.
 */
class ActionManager
{
    /**
     * @var DesignPattern[] An array of design patterns to be managed by the ActionManager.
     */
    protected array $designPatterns = [];

    /**
     * @var int The number of frames to include in the backtrace when identifying design patterns.
     */
    protected int $backtraceLimit = 10;

    /**
     * @var BacktraceFrameFactory Factory to create backtrace frames.
     */
    protected BacktraceFrameFactory $backtraceFrameFactory;

    /**
     * ActionManager constructor.
     *
     * Initializes the ActionManager with a BacktraceFrameFactory instance and an optional array of design patterns.
     *
     * @param BacktraceFrameFactory $backtraceFrameFactory The factory used to create backtrace frames.
     * @param DesignPattern[] $designPatterns An array of design patterns to be managed.
     */
    public function __construct(BacktraceFrameFactory $backtraceFrameFactory, array $designPatterns = [])
    {
        // Initialize the backtrace frame factory
        $this->backtraceFrameFactory = $backtraceFrameFactory;

        // Set the design patterns if provided
        $this->setDesignPatterns($designPatterns);
    }

    /**
     * Sets the backtrace limit for identifying design patterns.
     *
     * @param int $backtraceLimit The maximum number of backtrace frames to consider.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setBacktraceLimit(int $backtraceLimit): self
    {
        $this->backtraceLimit = $backtraceLimit;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Sets the design patterns to be managed by the ActionManager.
     *
     * @param DesignPattern[] $designPatterns An array of design patterns.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function setDesignPatterns(array $designPatterns): self
    {
        $this->designPatterns = $designPatterns;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Gets all the design patterns currently managed by the ActionManager.
     *
     * @return DesignPattern[] An array of the managed design patterns.
     */
    public function getDesignPatterns(): array
    {
        return $this->designPatterns;
    }

    /**
     * Registers a new design pattern with the ActionManager.
     *
     * @param DesignPattern $designPattern The design pattern to register.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function registerDesignPattern(DesignPattern $designPattern): self
    {
        $this->designPatterns[] = $designPattern;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Gets all design patterns that match the used traits of the provided action.
     *
     * @param array $usedTraits The traits used by the action to match.
     *
     * @return DesignPattern[] An array of design patterns matching the used traits.
     */
    public function getDesignPatternsMatching(array $usedTraits): array
    {
        // Filter design patterns based on the traits used by the action
        $filter = fn(DesignPattern $designPattern) => in_array($designPattern->getTrait(), $usedTraits);

        return Arr::filter($this->getDesignPatterns(), $filter);
    }

    /**
     * Identifies the design pattern from the backtrace, matching the used traits.
     *
     * @param array $usedTraits The traits used by the instance.
     * @param BacktraceFrame|null $frame The frame data to identify the design pattern.
     *
     * @return DesignPattern|null The identified design pattern or null if none is found.
     */
    public function identifyFromBacktrace($usedTraits, ?BacktraceFrame &$frame = null): ?DesignPattern
    {
        // Get the design patterns that match the used traits
        $designPatterns = $this->getDesignPatternsMatching($usedTraits);

        // Define the options for backtrace, providing object and ignoring arguments
        $backtraceOptions = DEBUG_BACKTRACE_PROVIDE_OBJECT
            | DEBUG_BACKTRACE_IGNORE_ARGS;

        // Limit the number of frames considered in the backtrace
        $ownNumberOfFrames = 2;
        $frames = Arr::slice(
            debug_backtrace($backtraceOptions, $ownNumberOfFrames + $this->backtraceLimit),
            $ownNumberOfFrames,
        );

        // Iterate through each frame in the backtrace
        foreach ($frames as $frame) {
            $frame = $this->backtraceFrameFactory->create(['frame' => $frame]);

            // Check each design pattern to see if it matches the current frame
            foreach ($designPatterns as $designPattern) {
                if ($designPattern->recognizeFrame($frame)) {
                    // Return the first matching design pattern
                    return $designPattern;
                }
            }
        }

        // Return null if no matching design pattern is found
        return null;
    }
}
