<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Exceptions;

use Maginium\Foundation\Enums\HttpStatusCode;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Support\Validator;
use RuntimeException;

/**
 * Class EntityNotFoundException.
 *
 * This exception is thrown when an entity (model) with a specific ID is not found in the database.
 * It provides additional information about the model and the ID, as well as a custom error message.
 */
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
     * The constructor ensures that the ID provided is either an integer or a string.
     * It also sets the message with a custom format indicating the model and ID that was not found.
     *
     * @param string $model The name of the affected model.
     * @param int|string $id The ID of the affected entity.
     *
     * @throws InvalidArgumentException if $id is not an integer or string.
     */
    public function __construct(string $model, $id)
    {
        // Validate the ID type to ensure it is either an integer or string
        if (! Validator::isInt($id) && ! Validator::isString($id)) {
            throw InvalidArgumentException::make('ID must be an integer or string.');
        }

        // Set the class properties with the model name and ID
        $this->id = (string)$id;
        $this->model = $model;

        // Build the exception message with the model name and ID
        $this->message = "No results found for model [{$model}] with ID #{$this->id}.";

        // Call the parent constructor to initialize the exception with HTTP status code 404 (Not Found)
        parent::__construct(
            $this->message, // The custom error message
            null,           // No previous exception or cause
            HttpStatusCode::NOT_FOUND, // Default HTTP status code 404 (Not Found)
        );
    }

    /**
     * Factory method to create a new instance of the EntityNotFoundException.
     *
     * This method provides a convenient way to instantiate the exception with the model and ID.
     * It helps to avoid direct instantiation by the class consumer.
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
     * This method returns the ID that caused the exception to be thrown. It helps to access
     * the affected entity's ID when handling or logging the exception.
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
     * This method returns the name of the model that caused the exception. It helps to access
     * the model name when handling or logging the exception.
     *
     * @return string The name of the affected model.
     */
    public function getModel(): string
    {
        return $this->model;
    }
}
