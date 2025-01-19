<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Traits;

use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Support\Reflection;

/**
 * Trait for making models sluggable.
 *
 * This trait provides functionality to handle slugs (URL-friendly identifiers) for models.
 * It includes methods to get and set the slug, as well as a customizable field name for storing the slug.
 *
 * @property string|null $slugKey The custom field name for storing the slug, if specified.
 */
trait Sluggable
{
    /**
     * Default field name for storing the slug.
     * If no custom field name is provided, this value will be used.
     *
     * @var string
     */
    private static $defaultSlugKey = 'slug';

    /**
     * Get the field name for storing the slug.
     *
     * This method retrieves the field name where the slug is stored in the model.
     * It checks if a custom field name is set in the class that uses this trait.
     * If no custom name is provided, it defaults to 'slug'.
     *
     * @return string The field name for storing the slug.
     */
    public static function getSlugKey(): string
    {
        // Check if the class using the trait has a custom `slugKey` property set.
        if (Reflection::propertyExists(static::class, 'slugKey')) {
            return static::$slugKey;
        }

        // Return the default field name if no custom name is set.
        return self::$defaultSlugKey;
    }

    /**
     * Get the slug for the model.
     *
     * This method retrieves the value of the slug from the model's data storage.
     * It returns the value stored in the `slugKey` field.
     *
     * @return string The slug of the model.
     */
    public function getSlug(): string
    {
        return $this->getData($this->getSlugKey());
    }

    /**
     * Set the slug for the model.
     *
     * This method sets a URL-friendly identifier (slug) for the model.
     * It assigns the provided slug to the field defined by `slugKey`.
     *
     * @param ?string $slug The slug to be set for the model.
     *
     * @return ModelInterface The current instance for method chaining.
     */
    public function setSlug(?string $slug): static
    {
        // Assign the slug value to the model's data.
        $this->setData($this->getSlugKey(), $slug);

        // Return the current instance to allow method chaining
        return $this;
    }
}
