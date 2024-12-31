<?php

declare(strict_types=1);

namespace Maginium\Framework\Actions\Exceptions;

use Exception;
use Throwable;

/**
 * Class MissingCommandSignatureException.
 *
 * Exception thrown when the command signature is missing from an action.
 * The action is expected to provide a `$commandSignature` property, but
 * if it's not set, this exception will be triggered. The exception message
 * includes the class name of the action to help identify which action is missing
 * the required command signature.
 */
class MissingCommandSignatureException extends Exception
{
    /**
     * Constructor for MissingCommandSignatureException.
     *
     * @param mixed $action The action that is missing the command signature.
     * @param int $code The error code (default is 0).
     * @param Throwable|null $previous The previous exception for chaining (default is null).
     */
    public function __construct($action, $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(
            __('The command signature is missing from your [%s] action. Use `public string $commandSignature` to set it up.', get_class($action)),
            $code,
            $previous,
        );
    }

    /**
     * Static factory method to create a new instance of MissingCommandSignatureException.
     *
     * @param mixed $action The action that is missing the command signature.
     * @param Throwable|null $cause The previous exception for chaining (optional).
     * @param int $code The error code associated with the exception (default is 0).
     *
     * @return self A new instance of the MissingCommandSignatureException.
     */
    public static function make($action, ?Throwable $cause = null, int $code = 0): self
    {
        return new self($action, $code, $cause);
    }
}
