<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Exceptions;

use Maginium\Foundation\Exceptions\Exception;

/**
 * Class InvalidModeException.
 *
 * Thrown when an invalid mode is provided.
 */
class InvalidModeException extends Exception
{
    /**
     * The exception message.
     *
     * @var string
     */
    protected $message = 'Invalid mode given. Please use "required" or "optional".';
}
