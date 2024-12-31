<?php

declare(strict_types=1);

namespace Maginium\Framework\Resize;

use Maginium\Framework\Resize\Interfaces\ResizerInterfaceFactory;

/**
 * ResizeManager builds resizers on demand.
 */
class ResizeManager
{
    private ResizerInterfaceFactory $factory;

    /**
     * ResizeManager constructor.
     */
    public function __construct(ResizerInterfaceFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Opens a file and returns a Resizer instance.
     */
    public function open(string $filename): Resizer
    {
        // Use the factory to create a new instance of Resizer and pass the filename to it.
        return $this->factory->create(compact('filename'));
    }
}
