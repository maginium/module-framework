<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Interfaces;

use Maginium\Foundation\Exceptions\BadMethodCallException;

/**
 * Interface Seoable.
 *
 * This interface defines methods to get and set SEO-related metadata (like meta title, description, etc.) for the Brand model.
 */
interface SeoableInterface
{
    /**
     * Get the SEO metadata for the model.
     * This method retrieves an array of common SEO fields like meta title, description, etc.
     *
     * @return array
     */
    public function getSeoData(): array;

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
    public function setSeoData(array $data): static;

    /**
     * Get the meta title for SEO.
     *
     * @return string|null
     */
    public function getMetaTitle();

    /**
     * Set the meta title for SEO.
     *
     * @param ?string $metaTitle
     *
     * @return static
     */
    public function setMetaTitle(?string $metaTitle);

    /**
     * Get the meta description for SEO.
     *
     * @return string|null
     */
    public function getMetaDescription();

    /**
     * Set the meta description for SEO.
     *
     * @param ?string $metaDescription
     *
     * @return static
     */
    public function setMetaDescription(?string $metaDescription);

    /**
     * Get the meta keywords for SEO.
     *
     * @return string|null
     */
    public function getMetaKeywords();

    /**
     * Set the meta keywords for SEO.
     *
     * @param ?string $metaKeywords
     *
     * @return static
     */
    public function setMetaKeywords(?string $metaKeywords);

    /**
     * Get the robots directive for SEO.
     *
     * @return string|null
     */
    public function getRobots(): ?string;

    /**
     * Set the robots directive for SEO.
     *
     * @param ?string $robots
     *
     * @return static
     */
    public function setRobots(?string $robots): static;

    /**
     * Get the H1 title for the brand.
     *
     * @return string|null
     */
    public function getH1Title(): ?string;

    /**
     * Set the H1 title for the brand.
     *
     * @param ?string $h1Title
     *
     * @return static
     */
    public function setH1Title(?string $h1Title): static;

    /**
     * Get the meta image for the brand.
     *
     * @return string|null
     */
    public function getMetaImage(): ?string;

    /**
     * Set the meta image for the brand.
     *
     * @param ?string $metaImage
     *
     * @return static
     */
    public function setMetaImage(?string $metaImage): static;

    /**
     * Get the canonical URL for the brand.
     *
     * @return string|null The canonical URL for the brand's SEO.
     */
    public function getCanonical(): ?string;

    /**
     * Set the canonical URL for the brand.
     *
     * @param ?string $canonical The canonical URL for the brand's SEO.
     *
     * @return static The current instance for method chaining.
     */
    public function setCanonical(?string $canonical): static;
}
