<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Traits;

use Maginium\Foundation\Exceptions\BadMethodCallException;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;

/**
 * Trait HasSeoData.
 *
 * This trait provides getter and setter methods for SEO-related data.
 * It allows easy access to model-related SEO fields such as meta title, meta description, robots directive, etc.
 *
 * @method ?string getMetaTitle() Get the meta title of the model for SEO.
 * @method ?string getMetaDescription() Get the meta description of the model for SEO.
 * @method ?string getMetaKeywords() Get the meta keywords of the model for SEO.
 * @method ?string getRobots() Get the robots directive for SEO.
 * @method ?string getH1Title() Get the H1 title for the model.
 * @method ?string getMetaImage() Get the meta image for the model.
 * @method ?string getMetaImage() Get the meta image for the model.
 * @method ?string getCanonical(?string $metaImage) Get the canonical URL for the model.
 * @method static setMetaTitle(?string $metaTitle) Set the meta title for SEO.
 * @method static setMetaDescription(?string $metaDescription) Set the meta description for SEO.
 * @method static setMetaKeywords(?string $metaKeywords) Set the meta keywords for SEO.
 * @method static setRobots(?string $robots) Set the robots directive for SEO.
 * @method static setH1Title(?string $h1Title) Set the H1 title for the model.
 * @method static setCanonical(?string $metaImage) Set the canonical URL for the model.
 */
trait Seoable
{
    /**
     * Meta title field.
     *
     * This constant represents the field name for the meta title of the model in the store.
     * It is used in database operations to reference the meta title.
     *
     * @var string
     */
    public const META_TITLE = 'meta_title';

    /**
     * Meta description field.
     *
     * This constant represents the field name for the meta description of the model in the store.
     * It is used in database operations to reference the meta description.
     *
     * @var string
     */
    public const META_DESCRIPTION = 'meta_description';

    /**
     * Meta keywords field.
     *
     * This constant represents the field name for the meta keywords of the model in the store.
     * It is used in database operations to reference the meta keywords.
     *
     * @var string
     */
    public const META_KEYWORDS = 'meta_keywords';

    /**
     * Robots field.
     *
     * This constant represents the field name for the robots directive of the model in the store.
     * It is used in database operations to reference the robots directive.
     *
     * @var string
     */
    public const ROBOTS = 'robots';

    /**
     * H1 title field.
     *
     * This constant represents the field name for the H1 title of the model in the store.
     * It is used in database operations to reference the H1 title for SEO purposes.
     *
     * @var string
     */
    public const META_H1_TITLE = 'h1_title';

    /**
     * Meta image field.
     *
     * This constant represents the field name for the meta image associated with the model.
     * It is used in database operations to reference the image used for SEO purposes.
     *
     * @var string
     */
    public const META_IMAGE = 'meta_image';

    /**
     * Canonical URL for SEO purposes.
     *
     * @var string
     */
    public const CANONICAL = 'canonical';

    /**
     * Get the SEO metadata for the model.
     * This method retrieves an array of common SEO fields like meta title, description, etc.
     *
     * @return array
     */
    public function getSeoData(): array
    {
        return [
            self::META_TITLE => $this->getMetaTitle(),
            self::META_DESCRIPTION => $this->getMetaDescription(),
            self::META_KEYWORDS => $this->getMetaKeywords(),
            self::ROBOTS => $this->getRobots(),
            self::META_H1_TITLE => $this->getH1Title(),
            self::META_IMAGE => $this->getMetaImage(),
        ];
    }

    /**
     * Set SEO data for the model.
     * This method sets the SEO fields like meta title, description, etc. from an array.
     *
     * @param array $data Associative array where keys are field names and values are the values to set.
     *
     * @throws BadMethodCallException If a setter method is not found for any key.
     *
     * @return static Returns the current model instance for method chaining.
     */
    public function setSeoData(array $data): static
    {
        // Loop through each key-value pair in the provided $data array
        foreach ($data as $key => $value) {
            // Generate the setter method name based on the key
            // For example, for 'meta_title' the method name will be 'setMetaTitle'
            $method = 'set' . Str::replace('_', '', Str::ucwords($key, '_'));

            // Check if the method exists in the logger class
            if (! Reflection::methodExists($this, $method)) {
                throw BadMethodCallException::make(__("Method '%1' does not exist.", $method));
            }

            // Call the method
            $this->{$method}($value);
        }

        // Return the current instance to allow for method chaining

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get the meta title for SEO.
     *
     * This method retrieves the meta title for the model, which is used for SEO optimization.
     * It pulls the value from the internal data storage using the constant for the meta title field.
     *
     * @return string|null The meta title for SEO, or null if not set.
     */
    public function getMetaTitle(): ?string
    {
        // Get the value of the meta title from the model's data using the defined constant
        return $this->getData(self::META_TITLE);
    }

    /**
     * Set the meta title for SEO.
     *
     * This method sets the meta title for the model, which is crucial for search engine optimization.
     * It stores the provided value in the model's data storage.
     *
     * @param ?string $metaTitle The meta title to set.
     *
     * @return static Returns the current model instance for method chaining.
     */
    public function setMetaTitle($metaTitle): static
    {
        // Set the provided meta title in the model's data storage
        $this->setData(self::META_TITLE, $metaTitle);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get the meta description for SEO.
     *
     * This method retrieves the meta description for the model, which is important for SEO as it describes the page content.
     * It pulls the value from the internal data storage using the constant for the meta description field.
     *
     * @return string|null The meta description for SEO, or null if not set.
     */
    public function getMetaDescription(): ?string
    {
        // Get the value of the meta description from the model's data using the defined constant
        return $this->getData(self::META_DESCRIPTION);
    }

    /**
     * Set the meta description for SEO.
     *
     * This method sets the meta description for the model, which helps search engines understand the content of the page.
     * It stores the provided value in the model's data storage.
     *
     * @param ?string $metaDescription The meta description to set.
     *
     * @return static Returns the current model instance for method chaining.
     */
    public function setMetaDescription($metaDescription): static
    {
        // Set the provided meta description in the model's data storage
        $this->setData(self::META_DESCRIPTION, $metaDescription);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get the meta keywords for SEO.
     *
     * This method retrieves the meta keywords for the model, which are used by search engines to understand the page's content.
     * It pulls the value from the internal data storage using the constant for the meta keywords field.
     *
     * @return string|null The meta keywords for SEO, or null if not set.
     */
    public function getMetaKeywords(): ?string
    {
        // Get the value of the meta keywords from the model's data using the defined constant
        return $this->getData(self::META_KEYWORDS);
    }

    /**
     * Set the meta keywords for SEO.
     *
     * This method sets the meta keywords for the model, which can help improve SEO by targeting specific keywords.
     * It stores the provided value in the model's data storage.
     *
     * @param ?string $metaKeywords The meta keywords to set.
     *
     * @return static Returns the current model instance for method chaining.
     */
    public function setMetaKeywords($metaKeywords): static
    {
        // Set the provided meta keywords in the model's data storage
        $this->setData(self::META_KEYWORDS, $metaKeywords);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get the robots directive for SEO.
     *
     * This method retrieves the robots directive for the model, which tells search engines how to index the page.
     * It pulls the value from the internal data storage using the constant for the robots directive field.
     *
     * @return string|null The robots directive for SEO, or null if not set.
     */
    public function getRobots(): ?string
    {
        // Get the value of the robots directive from the model's data using the defined constant
        return $this->getData(self::ROBOTS);
    }

    /**
     * Set the robots directive for SEO.
     *
     * This method sets the robots directive for the model, which controls how search engines should crawl and index the page.
     * It stores the provided value in the model's data storage.
     *
     * @param ?string $robots The robots directive to set (e.g., "index, follow").
     *
     * @return static Returns the current model instance for method chaining.
     */
    public function setRobots(?string $robots): static
    {
        // Set the provided robots directive in the model's data storage
        $this->setData(self::ROBOTS, $robots);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get the H1 title for the model.
     *
     * This method retrieves the H1 title for the model, which is typically used as the primary heading on the model page.
     * It pulls the value from the internal data storage using the constant for the H1 title field.
     *
     * @return string|null The H1 title for the model, or null if not set.
     */
    public function getH1Title(): ?string
    {
        // Get the value of the H1 title from the model's data using the defined constant
        return $this->getData(self::META_H1_TITLE);
    }

    /**
     * Set the H1 title for the model.
     *
     * This method sets the H1 title for the model, which serves as the main heading on the model's page.
     * It stores the provided value in the model's data storage.
     *
     * @param ?string $h1Title The H1 title to set.
     *
     * @return static Returns the current model instance for method chaining.
     */
    public function setH1Title(?string $h1Title): static
    {
        // Set the provided H1 title in the model's data storage
        $this->setData(self::META_H1_TITLE, $h1Title);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get the meta image for the model.
     *
     * This method retrieves the meta image for the model, which is used in SEO and social media sharing.
     * It pulls the value from the internal data storage using the constant for the meta image field.
     *
     * @return string|null The meta image for the model, or null if not set.
     */
    public function getMetaImage(): ?string
    {
        // Get the value of the meta image from the model's data using the defined constant
        return $this->getData(self::META_IMAGE);
    }

    /**
     * Set the meta image for the model.
     *
     * This method sets the meta image for the model, which is used for SEO and often displayed on social media platforms.
     * It stores the provided value in the model's data storage.
     *
     * @param ?string $metaImage The meta image URL to set.
     *
     * @return static Returns the current model instance for method chaining.
     */
    public function setMetaImage(?string $metaImage): static
    {
        // Set the provided meta image URL in the model's data storage
        $this->setData(self::META_IMAGE, $metaImage);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Get the canonical URL for the model.
     *
     * @return string
     */
    public function getCanonical(): ?string
    {
        return $this->getData(self::CANONICAL);
    }

    /**
     * Set the canonical URL for the model.
     *
     * @param ?string $canonical The canonical URL for the model's SEO.
     *
     * @return static The current instance for method chaining.
     */
    public function setCanonical(?string $canonical): static
    {
        // Assign the canonical URL to the model.
        $this->setData(self::CANONICAL, $canonical);

        // Return the current instance to allow method chaining
        return $this;
    }
}
