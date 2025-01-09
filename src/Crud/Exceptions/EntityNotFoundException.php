<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Exceptions;

use Maginium\Foundation\Enums\HttpStatusCode;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use RuntimeException;

class EntityNotFoundException extends RuntimeException
{
    /**
     * ID of the affected model.
     *
     * @var string
     */
    protected string $id;

    /**
     * Name of the affected model.
     *
     * @var string
     */
    protected string $model;

    /**
     * Constructor to initialize the exception with model and id details.
     *
     * @param string $model The name of the affected model.
     * @param int|string $id The ID of the affected entity.
     *
     * @throws InvalidArgumentException if $id is not an integer or string.
     */
    public function __construct(string $model, $id)
    {
        // Validate the ID type to ensure it is either an integer or string
        if (! is_int($id) && ! is_string($id)) {
            throw InvalidArgumentException::make('ID must be an integer or string.');
        }

        // Set the class properties
        $this->id = (string)$id;
        $this->model = $model;

        // Build the exception message
        $this->message = "No results found for model [{$model}] with ID #{$this->id}.";

        // Call the parent constructor to initialize the exception with HTTP status
        parent::__construct(
            $this->message, // The custom error message
            null,           // No previous exception or cause
            HttpStatusCode::NOT_FOUND, // Default HTTP status code 404 (Not Found)
        );
    }

    /**
     * Factory method to create a new instance of the EntityNotFoundException.
     *
     * @param string $model The name of the affected model.
     * @param int|string $id The ID of the affected entity.
     *
     * @return static The new exception instance.
     */
    public static function make(string $model, $id): self
    {
        return new self($model, $id);
    }

    /**
     * Get the ID of the affected model.
     *
     * @return string The ID of the affected model.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the name of the affected model.
     *
     * @return string The name of the affected model.
     */
    public function getModel(): string
    {
        return $this->model;
    }
}
