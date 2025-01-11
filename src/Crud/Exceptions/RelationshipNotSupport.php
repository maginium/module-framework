<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Exceptions;

use InvalidArgumentException;

/**
 * Class RelationshipNotSupport.
 *
 * Custom exception thrown when an unsupported relationship type is encountered.
 *
 * This exception is used when a relationship type (such as `hasOne`, `belongsTo`, etc.) is provided
 * but is not supported by the framework. The exception message includes a list of supported relationship types
 * to help guide the user or developer in using a valid relationship type.
 */
class RelationshipNotSupport extends InvalidArgumentException
{
    /**
     * List of supported relationship types.
     *
     * This static array holds the valid relationship types that the framework supports for Eloquent models.
     * When an unsupported relationship is encountered, this list will be included in the exception message.
     *
     * @var array
     */
    private static $supportedRelationships = [
        'hasOne',        // One-to-one relationship
        'belongsTo',     // Inverse of 'hasOne', one-to-one inverse relationship
        'hasMany',       // One-to-many relationship
        'belongsToMany', // Many-to-many relationship
    ];

    /**
     * Factory method to create a new instance of the exception.
     *
     * This method generates an exception with a detailed message indicating that the provided relationship type
     * is not supported. It also lists the supported relationship types for clarity.
     *
     * @return RelationshipNotSupport The created exception instance.
     */
    public static function make()
    {
        // Create the exception message indicating the unsupported relationship and list of supported ones.
        return new static(
            'Relationship not supported, supported relationships: ' . implode(', ', self::$supportedRelationships),
        );
    }
}
